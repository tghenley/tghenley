# 📣 Marketing Ideas

A planner full of marketing ideas, organized by month, where you choose **what you're currently doing** and **what you could do next**.

It ships pre-loaded with 60+ curated marketing ideas spanning 14 channels and every month of the year — plus the ability to add your own. Everything you do is saved automatically in your browser.

## ✨ Features

- **Dashboard** — at-a-glance stats (currently doing / could do / done), what's in progress, and ideas for the current month.
- **By Month** — a calendar grid with a theme for each month (New Year resolutions, Valentine's, Black Friday, holiday gifting…). Click a month to see every relevant idea.
- **Idea Library** — browse and filter the full library by category, month, effort, and status, or search by keyword/tag.
- **My Board** — a Kanban board (Could Do → Currently Doing → Done). Drag cards between columns to update status.
- **Three statuses per idea** — mark each idea as **Could Do**, **Currently Doing**, or **Done**.
- **Notes & plans** — attach your own notes, budget, owner, or results to any idea.
- **Add your own ideas** — title, description, category, months, effort/cost/impact, and tags.
- **Effort / Cost / Impact ratings** — prioritize quickly.
- **Export / Import** — back up or move your data as JSON.
- **Light & dark themes**, fully **responsive** (works on mobile), and **offline** — no server, no build step, no account.

## 🚀 Running it

It's a static site — no installation required.

**Option 1 — just open it:** double-click `index.html` (or open it in any browser).

**Option 2 — local server** (recommended, avoids file:// quirks):

```bash
python3 -m http.server 8000
# then visit http://localhost:8000
```

**Option 3 — GitHub Pages:** push this repo and enable Pages (Settings → Pages → deploy from branch). The app will be live at your Pages URL.

## 🗂️ Project structure

| File | Purpose |
|------|---------|
| `index.html` | App shell and layout |
| `styles.css` | All styling (dark/light themes, responsive) |
| `data.js` | The seed library of marketing ideas, categories, and month themes |
| `app.js` | Application logic, views, filtering, persistence |

## 🧠 How data works

- The curated ideas in `data.js` are read-only seed data.
- Your status changes, notes, and custom ideas are stored in `localStorage` and layered on top at runtime — so updates to the seed library never wipe your work.
- Use **Export data** to keep a JSON backup, and **Import data** to restore it on another device.

## ➕ Extending the idea library

Add a new object to the `SEED_IDEAS` array in `data.js`:

```js
{
  id: "unique-id",
  title: "Your idea",
  category: "social",        // must match a CATEGORIES id
  months: [11, 12],          // [] means evergreen / any time
  effort: "medium",          // low | medium | high
  cost: "low",               // free | low | medium | high
  impact: "high",            // low | medium | high
  description: "What it is and why it matters.",
  tags: ["example", "tag"]
}
```

## 🛣️ Ideas for future expansion

This is built to grow. Natural next steps: due dates & reminders, a budget tracker, results/ROI logging per idea, team assignments, an annual calendar/Gantt view, AI-generated idea suggestions, and cloud sync.
