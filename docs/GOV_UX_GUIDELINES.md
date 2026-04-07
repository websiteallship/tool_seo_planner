# GOV_UX_GUIDELINES — UI/UX Design Standards

## Nguyên Tắc Bắt Buộc

1. **UI text phải là tiếng Anh** — labels, buttons, headings, placeholders, table headers
2. **Comments/notes trong code ưu tiên tiếng Việt**
3. **100% icons phải là Lucide Icons** — NGHIÊM CẤM dùng emoji (🔑🤖✓✗⚠⚙) hoặc ký tự Unicode làm icon
4. **Theme mặc định: Light** — không có dark mode mặc định, không toggle dark/light khi khởi động

---

## Design System

### Color Tokens (Tailwind Config)
```javascript
// Dùng Tailwind classes chuẩn thay vì custom colors — light theme
// Primary: indigo (indigo-600, indigo-700)
// Success: green (green-500, green-600)
// Warning: yellow (yellow-500)
// Danger: red (red-500, red-600)
// Neutral: slate (slate-100 → slate-900)
// Background: white / slate-50 (light theme default)
```

### Intent Color Mapping
| Intent | Badge Color | Tailwind class |
|---|---|---|
| informational | Blue | `bg-blue-100 text-blue-700` |
| commercial | Orange | `bg-orange-100 text-orange-700` |
| transactional | Green | `bg-green-100 text-green-700` |
| navigational | Gray | `bg-slate-100 text-slate-600` |

### Status Color Mapping
| Status | Color | Dùng cho |
|---|---|---|
| active / pass / live | Green | Projects, Audit, Backlinks |
| paused / in_progress | Yellow | Projects, Audit |
| completed / approved | Blue | Projects, Outlines |
| archived / na | Gray | Projects, Audit |
| fail / lost / critical | Red | Audit, Backlinks |

---

## Layout Structure

### SPA Shell (index.html)
```
┌─────────────────────────────────────────────────┐
│  Top Bar (48px)                                  │
│  [Logo] [Project Switcher ▼] ─────── [User] [⚙] │
├────────────┬────────────────────────────────────┤
│ Sidebar     │ Content Area                       │
│ (220px)     │                                    │
│             │  ┌─ Page Header ─────────────────┐ │
│ [Dashboard] │  │ Title + Actions (CTA buttons)  │ │
│ [Keywords]  │  └───────────────────────────────┘ │
│ [Silo]      │                                    │
│ [Outline]   │  ┌─ Main Content ────────────────┐ │
│ [Audit]     │  │ (stats cards / table / tree)   │ │
│ [On-Page]   │  └───────────────────────────────┘ │
│ [GEO]       │                                    │
│ [Backlinks] │                                    │
│ [Rankings]  │                                    │
│ [GSC]       │                                    │
│ [Dashboard] │                                    │
│ [Reports]   │                                    │
│ [Quotation] │                                    │
│ [Settings]  │                                    │
└────────────┴────────────────────────────────────┘
```

---

## Component Patterns

### Stats Cards (Dashboard & Module headers)
```html
<!-- Dùng Lucide icon, KHÔNG dùng emoji -->
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="ts-stat-card">
    <div class="ts-stat-icon">
      <i data-lucide="key" class="w-5 h-5 text-indigo-500"></i>
    </div>
    <div class="ts-stat-value" x-text="stats.total"></div>
    <div class="ts-stat-label">Total Keywords</div>
  </div>
  <!-- repeat for other KPIs -->
</div>
```

### Data Tables
- Header: sticky, sortable columns
- Rows: hover highlight, checkbox select
- Empty state: icon + message + CTA button
- Loading: skeleton rows (3-5 rows placeholder)
- Pagination: Previous / Page X of Y / Next (50 rows/page)

### Sidebar Navigation
- Active item: `bg-indigo-50 text-indigo-700 font-medium`
- Inactive: `text-slate-600 hover:bg-slate-50`
- Icons: Lucide 16px, prefix before label
- Module count badge: inline right-aligned

### Modals (sl-dialog)
```html
<!-- Label modal: tiếng Anh -->
<sl-dialog :open="showModal" @sl-hide="showModal = false">
  <span slot="label">Add Keyword</span>
  <!-- form content -->
  <div slot="footer">
    <sl-button @click="showModal = false">Cancel</sl-button>
    <sl-button variant="primary" :loading="saving" @click="save()">Save</sl-button>
  </div>
</sl-dialog>
```

### Toast Notifications
- Success: green, Lucide icon `check-circle`, auto-dismiss 3s
- Error: red, Lucide icon `x-circle`, auto-dismiss 5s
- Warning: yellow, Lucide icon `alert-triangle`, auto-dismiss 4s
- Position: bottom-right
- **KHÔNG dùng ký tự ✓ ✗ ⚠** — phải dùng `<i data-lucide="...">` inline

### AI Preview Panel
```html
<!-- Hiển thị Gemini AI suggestions trước khi apply — KHÔNG dùng emoji 🤖 -->
<div class="ts-ai-preview-panel" x-show="aiPreview">
  <div class="ts-ai-badge">
    <i data-lucide="sparkles" class="w-4 h-4"></i>
    AI Suggestions — Review before applying
  </div>
  <!-- preview table -->
  <div class="flex gap-2 mt-4">
    <sl-button @click="aiPreview = null">Dismiss</sl-button>
    <sl-button variant="primary" @click="applyAI()">Apply All</sl-button>
  </div>
</div>
```

---

## Responsive Design (Mobile-First)

### Breakpoints (Tailwind defaults)
| Breakpoint | Width | Dùng cho |
|---|---|---|
| `default` | < 640px | Mobile |
| `sm` | ≥ 640px | Large mobile / small tablet |
| `md` | ≥ 768px | Tablet |
| `lg` | ≥ 1024px | Desktop — sidebar hiện cố định |

### Layout Patterns
- **Sidebar**: collapsible trên mobile (`-translate-x-full`), fixed trên `lg:` — overlay backdrop khi mở
- **Top bar**: hamburger menu `lg:hidden`, project switcher responsive width
- **Content area**: `ml-0 lg:ml-[220px]`, padding `p-3 sm:p-4 lg:p-6`
- **Tables**: wrap trong `.ts-table-scroll` (overflow-x-auto), ẩn cột phụ bằng `hidden sm:table-cell`
- **Stat cards**: `grid-cols-2 sm:grid-cols-4`
- **Filter bars**: `flex-wrap` cho mobile, full-width inputs
- **Modals**: full-width trên mobile (`max-width: calc(100vw - 2rem)`)
- **Action buttons**: luôn hiện trên touch devices (`@media (hover: none)`)
- **Settings grid**: `grid-cols-1 lg:grid-cols-2`

---

## Micro-copy Standards (UI = English)

| Context | Text |
|---|---|
| Empty table | "No data yet. [+ Add New]" |
| Loading | "Loading..." |
| AI processing | "AI is analyzing..." |
| Save success | "Saved successfully" |
| Delete confirm | "Are you sure? This action cannot be undone." |
| Sync GSC | "Syncing from Google Search Console..." |
| Import CSV | "{n} keywords imported" |
| AI preview | "AI suggested {n} changes. Please review before applying." |
| Error generic | "Something went wrong. Please try again." |

---

## Shoelace Components Allowed List

| Component | Used for |
|---|---|
| `sl-button` | All buttons |
| `sl-dialog` | Modals |
| `sl-badge` | Status, intent labels |
| `sl-input` | Text inputs |
| `sl-select` | Dropdowns |
| `sl-checkbox` | Checkboxes |
| `sl-tab` / `sl-tab-group` | Tabbed views |
| `sl-progress-bar` | Progress indicators |
| `sl-tooltip` | Tooltips |
| `sl-spinner` | Loading inline |
| `sl-alert` | Warnings, info banners |
| `sl-tree` / `sl-tree-item` | Silo module tree view |
| `sl-card` | Card containers |

## Icon Usage Rules

```html
<!-- ✅ ĐÚNG — Lucide icon -->
<i data-lucide="plus" class="w-4 h-4"></i>
<i data-lucide="trash-2" class="w-4 h-4"></i>
<i data-lucide="download" class="w-4 h-4"></i>
<i data-lucide="sparkles" class="w-4 h-4"></i>  <!-- AI features -->
<i data-lucide="settings" class="w-4 h-4"></i>
<i data-lucide="check-circle" class="w-4 h-4"></i>
<i data-lucide="x-circle" class="w-4 h-4"></i>
<i data-lucide="alert-triangle" class="w-4 h-4"></i>

<!-- ❌ SAI — emoji/ký tự Unicode làm icon -->
🔑 🤖 ✓ ✗ ⚠ ⚙ — NGHIÊM CẤM dùng trong UI
```

## Theme

- **Default: Light theme** — `bg-white`, `text-slate-900`, sidebar `bg-slate-50`
- Dark mode: KHÔNG triển khai (Phase 4, nice-to-have)
- KHÔNG dùng `dark:` Tailwind prefix trong MVP
