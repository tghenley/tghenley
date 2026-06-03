/* ===========================================================================
   Marketing Ideas — Application Logic
   ---------------------------------------------------------------------------
   - Seed ideas live in data.js and are never mutated.
   - User state (status overrides, notes, and custom ideas) is persisted to
     localStorage and layered on top of the seed at runtime.
   =========================================================================== */

(function () {
  "use strict";

  const STORAGE_KEY = "marketing-ideas-v1";
  const STATUSES = {
    idea:  { label: "Could Do",       short: "Idea",  color: "var(--idea)" },
    doing: { label: "Currently Doing", short: "Doing", color: "var(--doing)" },
    done:  { label: "Done",           short: "Done",  color: "var(--done)" },
  };

  // ---- State --------------------------------------------------------------
  let state = loadState();
  let currentView = "dashboard";
  let filters = { category: "all", status: "all", month: "all", effort: "all", search: "" };
  const catMap = Object.fromEntries(CATEGORIES.map((c) => [c.id, c]));

  function defaultState() {
    return { overrides: {}, notes: {}, custom: [], theme: "dark" };
  }

  function loadState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return defaultState();
      return Object.assign(defaultState(), JSON.parse(raw));
    } catch (e) {
      console.warn("Could not load saved data:", e);
      return defaultState();
    }
  }

  function saveState() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (e) {
      toast("⚠️ Could not save — storage may be full");
    }
  }

  // ---- Data access --------------------------------------------------------
  // Merge seed + custom ideas, applying per-idea status & note overrides.
  function allIdeas() {
    const merged = SEED_IDEAS.concat(state.custom);
    return merged.map((idea) => ({
      ...idea,
      status: state.overrides[idea.id] || idea.defaultStatus || "idea",
      note: state.notes[idea.id] || "",
    }));
  }

  function setStatus(id, status) {
    if (state.overrides[id] === status) {
      // Toggling the active status returns it to "idea".
      delete state.overrides[id];
    } else {
      state.overrides[id] = status;
    }
    saveState();
    render();
  }

  function setNote(id, note) {
    if (note && note.trim()) state.notes[id] = note.trim();
    else delete state.notes[id];
    saveState();
  }

  // ---- Filtering ----------------------------------------------------------
  function applyFilters(ideas) {
    const q = filters.search.trim().toLowerCase();
    return ideas.filter((i) => {
      if (filters.category !== "all" && i.category !== filters.category) return false;
      if (filters.status !== "all" && i.status !== filters.status) return false;
      if (filters.effort !== "all" && i.effort !== filters.effort) return false;
      if (filters.month !== "all") {
        const m = parseInt(filters.month, 10);
        const inMonth = i.months.includes(m) || i.months.length === 0;
        if (!inMonth) return false;
      }
      if (q) {
        const hay = [i.title, i.description, (i.tags || []).join(" "),
          catMap[i.category]?.label].join(" ").toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });
  }

  // ---- Rendering: shared components --------------------------------------
  function el(html) {
    const t = document.createElement("template");
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
  }

  function esc(s) {
    return String(s ?? "").replace(/[&<>"']/g, (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  }

  function monthLabel(months) {
    if (!months || months.length === 0) return "Evergreen";
    if (months.length > 3) return months.length + " months";
    return months.map((m) => MONTH_NAMES[m - 1].slice(0, 3)).join(", ");
  }

  function ideaCard(i) {
    const cat = catMap[i.category] || { label: i.category, icon: "•", color: "#888" };
    const card = el(`
      <article class="idea-card" data-status="${i.status}">
        <div class="idea-top">
          <span class="idea-cat" style="background:${cat.color}22;color:${cat.color}">
            ${cat.icon} ${esc(cat.label)}
          </span>
          ${i.custom ? '<span class="custom-badge">YOURS</span>' : ""}
        </div>
        <div class="idea-title">${esc(i.title)}</div>
        <div class="idea-desc">${esc(i.description)}</div>
        <div class="idea-meta">
          <span class="chip">🗓 ${esc(monthLabel(i.months))}</span>
          <span class="chip effort-${i.effort}">⚡ ${i.effort} effort</span>
          <span class="chip cost-${i.cost}">💵 ${i.cost === "free" ? "free" : i.cost + " cost"}</span>
          <span class="chip impact-${i.impact}">📈 ${i.impact} impact</span>
        </div>
        ${(i.tags && i.tags.length)
          ? `<div class="idea-tags">${i.tags.map((t) => `<span class="tag">${esc(t)}</span>`).join("")}</div>`
          : ""}
        ${i.note ? `<div class="idea-note">${esc(i.note)}</div>` : ""}
        <button class="idea-note-btn">📝 ${i.note ? "Edit note" : "Add note"}</button>
        <div class="idea-actions">
          ${Object.entries(STATUSES).map(([k, v]) =>
            `<button class="status-btn ${i.status === k ? "active" : ""}" data-s="${k}">${v.label}</button>`
          ).join("")}
        </div>
      </article>`);

    card.querySelectorAll(".status-btn").forEach((btn) =>
      btn.addEventListener("click", () => setStatus(i.id, btn.dataset.s)));
    card.querySelector(".idea-note-btn").addEventListener("click", () => openNoteModal(i));
    return card;
  }

  function renderCardGrid(container, ideas, emptyMsg) {
    if (!ideas.length) {
      container.appendChild(el(`<div class="empty"><div class="big">🤔</div>${esc(emptyMsg || "No ideas match your filters.")}</div>`));
      return;
    }
    const grid = el('<div class="card-grid"></div>');
    ideas.forEach((i) => grid.appendChild(ideaCard(i)));
    container.appendChild(grid);
  }

  // ---- View: Dashboard ----------------------------------------------------
  function renderDashboard(c) {
    const ideas = allIdeas();
    const total = ideas.length;
    const doing = ideas.filter((i) => i.status === "doing");
    const done = ideas.filter((i) => i.status === "done");
    const couldDo = ideas.filter((i) => i.status === "idea");
    const month = new Date().getMonth() + 1;
    const thisMonth = ideas.filter((i) => i.months.includes(month));
    const pct = (n) => total ? Math.round((n / total) * 100) : 0;

    c.appendChild(el(`
      <div class="stat-grid">
        <div class="stat-card">
          <div class="num">${doing.length}</div><div class="lbl">Currently doing</div>
          <div class="bar"><i style="width:${pct(doing.length)}%;background:var(--doing)"></i></div>
        </div>
        <div class="stat-card">
          <div class="num">${couldDo.length}</div><div class="lbl">Ideas to consider</div>
          <div class="bar"><i style="width:${pct(couldDo.length)}%;background:var(--idea)"></i></div>
        </div>
        <div class="stat-card">
          <div class="num">${done.length}</div><div class="lbl">Completed</div>
          <div class="bar"><i style="width:${pct(done.length)}%;background:var(--done)"></i></div>
        </div>
        <div class="stat-card">
          <div class="num">${total}</div><div class="lbl">Total ideas in library</div>
          <div class="bar"><i style="width:100%;background:var(--primary)"></i></div>
        </div>
      </div>`));

    // Currently doing
    c.appendChild(sectionHead("🔥 What you're doing right now", doing.length));
    renderCardGrid(c, doing, "Nothing in progress yet. Browse the library and mark a few as “Currently Doing”.");

    // Ideas for this month
    const m = MONTH_THEMES[month - 1];
    c.appendChild(sectionHead(`${m.emoji} Ideas for ${m.name} — ${m.theme}`, thisMonth.length));
    renderCardGrid(c, thisMonth.filter((i) => i.status !== "done").slice(0, 6),
      "No seasonal ideas this month — try the evergreen library.");
  }

  // ---- View: Calendar / By Month -----------------------------------------
  function renderCalendar(c) {
    const ideas = allIdeas();
    const currentMonth = new Date().getMonth() + 1;
    const grid = el('<div class="month-grid"></div>');

    MONTH_THEMES.forEach((m, idx) => {
      const monthNum = idx + 1;
      const count = ideas.filter((i) => i.months.includes(monthNum)).length;
      const doingCount = ideas.filter((i) => i.months.includes(monthNum) && i.status === "doing").length;
      const card = el(`
        <div class="month-card ${monthNum === currentMonth ? "current" : ""}">
          <div class="mtop">
            <div>
              <div class="mname">${m.name}</div>
              ${monthNum === currentMonth ? '<span class="now-badge">● THIS MONTH</span>' : ""}
            </div>
            <span class="memoji">${m.emoji}</span>
          </div>
          <div class="mtheme">${esc(m.theme)}</div>
          <div class="mcount">${count} ideas${doingCount ? ` · ${doingCount} active` : ""}</div>
        </div>`);
      card.addEventListener("click", () => {
        filters.month = String(monthNum);
        filters.category = "all"; filters.status = "all"; filters.effort = "all";
        switchView("library");
      });
      grid.appendChild(card);
    });

    c.appendChild(sectionHead("📅 Pick a month to plan", ""));
    c.appendChild(grid);

    const evergreen = ideas.filter((i) => i.months.length === 0);
    c.appendChild(sectionHead("♾️ Evergreen ideas (any time of year)", evergreen.length));
    renderCardGrid(c, evergreen);
  }

  // ---- View: Library ------------------------------------------------------
  function renderLibrary(c) {
    const bar = el(`
      <div class="filters">
        <select id="fCategory"><option value="all">All categories</option>
          ${CATEGORIES.map((cat) => `<option value="${cat.id}">${cat.icon} ${esc(cat.label)}</option>`).join("")}
        </select>
        <select id="fMonth"><option value="all">All months</option>
          ${MONTH_NAMES.map((n, idx) => `<option value="${idx + 1}">${n}</option>`).join("")}
        </select>
        <select id="fEffort"><option value="all">Any effort</option>
          <option value="low">Low effort</option><option value="medium">Medium effort</option><option value="high">High effort</option>
        </select>
        <div class="seg" id="fStatus">
          <button data-v="all" class="active">All</button>
          <button data-v="idea">Could Do</button>
          <button data-v="doing">Doing</button>
          <button data-v="done">Done</button>
        </div>
        <button class="btn ghost sm" id="clearFilters">Clear</button>
      </div>`);
    c.appendChild(bar);

    bar.querySelector("#fCategory").value = filters.category;
    bar.querySelector("#fMonth").value = filters.month;
    bar.querySelector("#fEffort").value = filters.effort;
    bar.querySelectorAll("#fStatus button").forEach((b) =>
      b.classList.toggle("active", b.dataset.v === filters.status));

    bar.querySelector("#fCategory").addEventListener("change", (e) => { filters.category = e.target.value; render(); });
    bar.querySelector("#fMonth").addEventListener("change", (e) => { filters.month = e.target.value; render(); });
    bar.querySelector("#fEffort").addEventListener("change", (e) => { filters.effort = e.target.value; render(); });
    bar.querySelectorAll("#fStatus button").forEach((b) =>
      b.addEventListener("click", () => { filters.status = b.dataset.v; render(); }));
    bar.querySelector("#clearFilters").addEventListener("click", () => {
      filters = { category: "all", status: "all", month: "all", effort: "all", search: filters.search };
      document.getElementById("searchInput").value = filters.search;
      render();
    });

    const filtered = applyFilters(allIdeas());
    c.appendChild(sectionHead("💡 Idea library", filtered.length));
    renderCardGrid(c, filtered);
  }

  // ---- View: Kanban board -------------------------------------------------
  function renderBoard(c) {
    const ideas = applyFilters(allIdeas());
    const cols = [
      { key: "idea",  title: "Could Do",        color: "var(--idea)" },
      { key: "doing", title: "Currently Doing",  color: "var(--doing)" },
      { key: "done",  title: "Done",            color: "var(--done)" },
    ];
    const board = el('<div class="board"></div>');

    cols.forEach((col) => {
      const items = ideas.filter((i) => i.status === col.key);
      const colEl = el(`
        <div class="board-col" data-status="${col.key}">
          <h3><span class="dot" style="background:${col.color}"></span>${col.title}
            <span class="col-count">${items.length}</span></h3>
        </div>`);

      items.forEach((i) => {
        const cat = catMap[i.category] || {};
        const card = el(`
          <div class="board-card" draggable="true" data-id="${i.id}">
            <div class="bc-title">${esc(i.title)}</div>
            <div class="bc-cat">${cat.icon || ""} ${esc(cat.label || i.category)} · ${esc(monthLabel(i.months))}</div>
          </div>`);
        card.addEventListener("dragstart", (e) => {
          card.classList.add("dragging");
          e.dataTransfer.setData("text/plain", i.id);
        });
        card.addEventListener("dragend", () => card.classList.remove("dragging"));
        card.addEventListener("click", () => openNoteModal(i));
        colEl.appendChild(card);
      });

      colEl.addEventListener("dragover", (e) => { e.preventDefault(); colEl.classList.add("drag-over"); });
      colEl.addEventListener("dragleave", () => colEl.classList.remove("drag-over"));
      colEl.addEventListener("drop", (e) => {
        e.preventDefault();
        colEl.classList.remove("drag-over");
        const id = e.dataTransfer.getData("text/plain");
        if (id) { state.overrides[id] = col.key; saveState(); render(); }
      });

      board.appendChild(colEl);
    });

    c.appendChild(el('<div class="filters"><span class="sub" style="color:var(--text-dim);font-size:13px;font-weight:600">Drag cards between columns to update their status. Click a card to add notes.</span></div>'));
    c.appendChild(board);
  }

  // ---- Modals -------------------------------------------------------------
  function closeModal() { document.getElementById("modalRoot").innerHTML = ""; }

  function openNoteModal(i) {
    const root = document.getElementById("modalRoot");
    const modal = el(`
      <div class="modal-backdrop">
        <div class="modal">
          <div class="modal-head"><h2>📝 Notes & plan</h2><button class="x">×</button></div>
          <div class="modal-body">
            <div style="font-weight:700;font-size:15px">${esc(i.title)}</div>
            <div style="color:var(--text-dim);font-size:13.5px">${esc(i.description)}</div>
            <div class="field">
              <label>Your notes, plan, results…</label>
              <textarea id="noteText" placeholder="e.g. Targeting launch for week 2. Budget $500. Owner: me.">${esc(i.note)}</textarea>
            </div>
            <div class="field">
              <label>Status</label>
              <select id="noteStatus">
                ${Object.entries(STATUSES).map(([k, v]) =>
                  `<option value="${k}" ${i.status === k ? "selected" : ""}>${v.label}</option>`).join("")}
              </select>
            </div>
          </div>
          <div class="modal-foot">
            <button class="btn ghost" id="cancelNote">Cancel</button>
            <button class="btn primary" id="saveNote">Save</button>
          </div>
        </div>
      </div>`);
    root.appendChild(modal);
    const close = () => closeModal();
    modal.querySelector(".x").addEventListener("click", close);
    modal.querySelector("#cancelNote").addEventListener("click", close);
    modal.addEventListener("click", (e) => { if (e.target === modal) close(); });
    modal.querySelector("#saveNote").addEventListener("click", () => {
      setNote(i.id, modal.querySelector("#noteText").value);
      state.overrides[i.id] = modal.querySelector("#noteStatus").value;
      saveState();
      close();
      toast("✅ Saved");
      render();
    });
  }

  function openAddModal() {
    const root = document.getElementById("modalRoot");
    const modal = el(`
      <div class="modal-backdrop">
        <div class="modal">
          <div class="modal-head"><h2>＋ Add your own idea</h2><button class="x">×</button></div>
          <div class="modal-body">
            <div class="field">
              <label>Idea title *</label>
              <input id="aTitle" placeholder="e.g. Launch a customer podcast" />
            </div>
            <div class="field">
              <label>Description</label>
              <textarea id="aDesc" placeholder="What is it and why does it matter?"></textarea>
            </div>
            <div class="field">
              <label>Category</label>
              <select id="aCat">${CATEGORIES.map((c) => `<option value="${c.id}">${c.icon} ${esc(c.label)}</option>`).join("")}</select>
            </div>
            <div class="field">
              <label>Relevant months <span style="color:var(--text-mute);font-weight:500">(leave empty for evergreen — Ctrl/Cmd-click for multiple)</span></label>
              <select id="aMonths" multiple size="4">
                ${MONTH_NAMES.map((n, idx) => `<option value="${idx + 1}">${n}</option>`).join("")}
              </select>
            </div>
            <div class="field-row">
              <div class="field"><label>Effort</label><select id="aEffort"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
              <div class="field"><label>Cost</label><select id="aCost"><option value="free">Free</option><option value="low" selected>Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
              <div class="field"><label>Impact</label><select id="aImpact"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
            </div>
            <div class="field">
              <label>Tags <span style="color:var(--text-mute);font-weight:500">(comma separated)</span></label>
              <input id="aTags" placeholder="podcast, audio, brand" />
            </div>
          </div>
          <div class="modal-foot">
            <button class="btn ghost" id="cancelAdd">Cancel</button>
            <button class="btn primary" id="saveAdd">Add idea</button>
          </div>
        </div>
      </div>`);
    root.appendChild(modal);
    const close = () => closeModal();
    modal.querySelector(".x").addEventListener("click", close);
    modal.querySelector("#cancelAdd").addEventListener("click", close);
    modal.addEventListener("click", (e) => { if (e.target === modal) close(); });
    modal.querySelector("#aTitle").focus();
    modal.querySelector("#saveAdd").addEventListener("click", () => {
      const title = modal.querySelector("#aTitle").value.trim();
      if (!title) { toast("⚠️ Please enter a title"); return; }
      const months = Array.from(modal.querySelector("#aMonths").selectedOptions).map((o) => parseInt(o.value, 10));
      const tags = modal.querySelector("#aTags").value.split(",").map((t) => t.trim()).filter(Boolean);
      const idea = {
        id: "custom-" + Date.now().toString(36),
        title,
        description: modal.querySelector("#aDesc").value.trim() || "(no description)",
        category: modal.querySelector("#aCat").value,
        months,
        effort: modal.querySelector("#aEffort").value,
        cost: modal.querySelector("#aCost").value,
        impact: modal.querySelector("#aImpact").value,
        tags,
        custom: true,
      };
      state.custom.push(idea);
      saveState();
      close();
      toast("✅ Idea added to your library");
      render();
    });
  }

  // ---- Helpers ------------------------------------------------------------
  function sectionHead(title, count) {
    return el(`<div class="section-head"><h2>${esc(title)}</h2>${count !== "" ? `<span class="count">${count}</span>` : ""}<span class="line"></span></div>`);
  }

  let toastTimer;
  function toast(msg) {
    const t = document.getElementById("toast");
    t.textContent = msg;
    t.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove("show"), 2200);
  }

  const VIEW_META = {
    dashboard: { title: "Dashboard", sub: "Your marketing command center" },
    calendar:  { title: "By Month", sub: "Seasonal ideas all year round" },
    library:   { title: "Idea Library", sub: "Browse, filter & plan every idea" },
    board:     { title: "My Board", sub: "Drag ideas across Could Do → Doing → Done" },
  };

  function switchView(view) {
    currentView = view;
    document.querySelectorAll(".nav-item").forEach((n) =>
      n.classList.toggle("active", n.dataset.view === view));
    document.getElementById("viewTitle").textContent = VIEW_META[view].title;
    document.getElementById("viewSub").textContent = VIEW_META[view].sub;
    closeSidebar();
    render();
  }

  function render() {
    const c = document.getElementById("content");
    c.innerHTML = "";
    if (currentView === "dashboard") renderDashboard(c);
    else if (currentView === "calendar") renderCalendar(c);
    else if (currentView === "library") renderLibrary(c);
    else if (currentView === "board") renderBoard(c);
  }

  // ---- Import / Export ----------------------------------------------------
  function exportData() {
    const blob = new Blob([JSON.stringify(state, null, 2)], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `marketing-ideas-${new Date().toISOString().slice(0, 10)}.json`;
    a.click();
    URL.revokeObjectURL(url);
    toast("⬇ Exported your data");
  }

  function importData(file) {
    const reader = new FileReader();
    reader.onload = () => {
      try {
        const data = JSON.parse(reader.result);
        state = Object.assign(defaultState(), data);
        saveState();
        applyTheme();
        render();
        toast("⬆ Data imported");
      } catch (e) {
        toast("⚠️ Invalid file");
      }
    };
    reader.readAsText(file);
  }

  // ---- Theme & sidebar ----------------------------------------------------
  function applyTheme() {
    document.documentElement.setAttribute("data-theme", state.theme || "dark");
  }
  function toggleTheme() {
    state.theme = state.theme === "dark" ? "light" : "dark";
    saveState();
    applyTheme();
  }
  function openSidebar() { document.getElementById("sidebar").classList.add("open"); document.getElementById("scrim").classList.add("show"); }
  function closeSidebar() { document.getElementById("sidebar").classList.remove("open"); document.getElementById("scrim").classList.remove("show"); }

  // ---- Wire up ------------------------------------------------------------
  function init() {
    applyTheme();

    document.querySelectorAll(".nav-item").forEach((n) =>
      n.addEventListener("click", () => switchView(n.dataset.view)));

    document.getElementById("searchInput").addEventListener("input", (e) => {
      filters.search = e.target.value;
      if (currentView !== "library" && currentView !== "board") switchView("library");
      else render();
    });

    document.getElementById("addIdeaBtn").addEventListener("click", openAddModal);
    document.getElementById("themeBtn").addEventListener("click", toggleTheme);
    document.getElementById("exportBtn").addEventListener("click", exportData);
    document.getElementById("importBtn").addEventListener("click", () => document.getElementById("importFile").click());
    document.getElementById("importFile").addEventListener("change", (e) => {
      if (e.target.files[0]) importData(e.target.files[0]);
      e.target.value = "";
    });
    document.getElementById("resetBtn").addEventListener("click", () => {
      if (confirm("Reset everything? This clears all your statuses, notes, and custom ideas.")) {
        state = defaultState();
        saveState();
        applyTheme();
        render();
        toast("↺ Reset complete");
      }
    });

    document.getElementById("menuToggle").addEventListener("click", openSidebar);
    document.getElementById("scrim").addEventListener("click", closeSidebar);
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") closeModal(); });

    render();
  }

  document.addEventListener("DOMContentLoaded", init);
})();
