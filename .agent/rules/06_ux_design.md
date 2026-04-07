# Rule: UI/UX Design Standards

## 4 Nguyên Tắc Bắt Buộc

| # | Nguyên tắc | Quy tắc |
|---|---|---|
| 1 | Ngôn ngữ UI | **Tiếng Anh** — tất cả labels, buttons, headings, placeholders, table headers |
| 2 | Ngôn ngữ code | Comments/notes trong `.gs` và `.html` → **ưu tiên tiếng Việt** |
| 3 | Icons | **100% Lucide Icons** — `<i data-lucide="name">` — NGHIÊM CẤM emoji/ký tự Unicode |
| 4 | Theme | **Light theme mặc định** — KHÔNG dark mode, KHÔNG `dark:` prefix trong MVP |

---

## Design System

### Color Tokens
```
Primary:   indigo-600 / indigo-700
Success:   green-500 / green-600
Warning:   yellow-500
Danger:    red-500 / red-600
Neutral:   slate-100 → slate-900
```

### Intent Color Mapping
| Intent | Tailwind |
|---|---|
| informational | `bg-blue-100 text-blue-700` |
| commercial | `bg-orange-100 text-orange-700` |
| transactional | `bg-green-100 text-green-700` |
| navigational | `bg-slate-100 text-slate-600` |

### Status Color Mapping
| Status | Color |
|---|---|
| active / pass / live | Green |
| paused / in_progress | Yellow |
| completed / approved | Blue |
| archived / na | Gray |
| fail / lost / critical | Red |

## SPA Layout

```
┌─────────────────────────────────────────────────┐
│  Top Bar (48px): Logo · Project Switcher · User  │
├──────────────┬──────────────────────────────────┤
│ Sidebar      │ Content Area                     │
│ (220px)      │  ┌─ Page Header ───────────────┐ │
│              │  │ Title + CTA buttons          │ │
│ [Module nav] │  └─────────────────────────────┘ │
│              │  ┌─ Main Content ──────────────┐ │
│              │  │ stats cards / table / tree  │ │
│              │  └─────────────────────────────┘ │
└──────────────┴──────────────────────────────────┘
```

- Sidebar active: `bg-indigo-50 text-indigo-700 font-medium`
- Sidebar inactive: `text-slate-600 hover:bg-slate-50`
- Sidebar icons: Lucide 16px

## Shoelace Components (Allowed List)

| Component | Dùng cho |
|---|---|
| `sl-button` | Tất cả buttons |
| `sl-dialog` | Modals |
| `sl-badge` | Status, intent labels |
| `sl-input` | Text inputs |
| `sl-select` | Dropdowns |
| `sl-checkbox` | Checkboxes |
| `sl-tab` / `sl-tab-group` | Tabbed views |
| `sl-progress-bar` | Progress |
| `sl-tooltip` | Tooltips |
| `sl-spinner` | Loading inline |
| `sl-alert` | Warnings, info banners |
| `sl-tree` / `sl-tree-item` | Silo tree view |
| `sl-card` | Card containers |

## Component Patterns

### Modal (sl-dialog)
```html
<!-- Label modal: tiếng Anh. Button text: tiếng Anh -->
<sl-dialog :open="showModal" @sl-hide="showModal = false">
  <span slot="label">Add Keyword</span>
  <!-- form content -->
  <div slot="footer">
    <sl-button @click="showModal = false">Cancel</sl-button>
    <sl-button variant="primary" :loading="saving" @click="save()">Save</sl-button>
  </div>
</sl-dialog>
```

### Loading States (BẮT BUỘC cho mọi async action)
```html
<sl-button :loading="loading" @click="loadData()">Tải dữ liệu</sl-button>
<template x-if="loading">
  <div class="ts-skeleton-table">...</div>
</template>
```

### AI Preview Panel
```html
<!-- Panel Gemini AI suggestions — KHÔNG dùng emoji 🤖. Button text: tiếng Anh -->
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

### Stats Cards (Dashboard & Module headers)
```html
<!-- Dùng Lucide icon — KHÔNG dùng emoji như 🔑 🤖 ✓ -->
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="ts-stat-card">
    <div class="ts-stat-icon">
      <i data-lucide="key" class="w-5 h-5 text-indigo-500"></i>
    </div>
    <div class="ts-stat-value" x-text="stats.total"></div>
    <div class="ts-stat-label">Total Keywords</div>
  </div>
</div>
```

### Toast Notifications
- Success: green, Lucide icon `check-circle`, auto-dismiss 3s
- Error: red, Lucide icon `x-circle`, auto-dismiss 5s
- Warning: yellow, Lucide icon `alert-triangle`, auto-dismiss 4s
- Position: bottom-right
- **KHÔNG dùng ký tự ✓ ✗ ⚠** — phải dùng `<i data-lucide="...">`

## Micro-copy Standards (English)

| Context | Text |
|---|---|
| Sync GSC | "Đang đồng bộ từ Google Search Console..." |
| Import CSV | "Đã import {n} keywords" |
| AI preview | "AI đề xuất {n} thay đổi. Vui lòng review trước khi áp dụng." |

## Responsive

- **Min width: 1280px** — internal tool, không hỗ trợ mobile
- Sidebar: fixed 220px
- Tables: horizontal scroll nếu overflow
- Pagination: 50 rows/page

## Data Table Checklist

- [ ] Header: sticky + sortable columns
- [ ] Rows: hover highlight + checkbox select
- [ ] Empty state: icon + message + CTA button
- [ ] Loading: skeleton rows (3-5 rows)
- [ ] Pagination: "Previous / Page X of Y / Next"
