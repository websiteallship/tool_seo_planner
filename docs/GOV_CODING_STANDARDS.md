# GOV_CODING_STANDARDS — Coding Standards & Conventions

## Nguyên Tắc Nền Tảng

| Nguyên tắc | Quy tắc |
|---|---|
| Ngôn ngữ UI | **Tiếng Anh** — tất cả labels, buttons, headings, placeholders, table headers |
| Ngôn ngữ code | Comments, notes, giải thích trong `.gs` / `.html` → **ưu tiên tiếng Việt** |
| Icon UI | **100% Lucide Icons** — `<i data-lucide="name">` — NGHIÊM CẤM emoji/ký tự Unicode |
| AI Provider | **Chỉ Gemini** (`gemini-2.0-flash`) — không tích hợp OpenAI, Claude, hay AI khác |
| Default Theme | **Light theme** — `bg-white / slate-50` — không dark mode mặc định |

---

## Naming Conventions

### GAS (Server-side)
| Loại | Convention | Ví dụ |
|---|---|---|
| Service files | `PascalCase.gs` | `KeywordService.gs` |
| Utility files | `PascalCase.gs` | `SheetDB.gs`, `Utils.gs` |
| Functions public | `camelCase` | `getAll()`, `addKeyword()` |
| Functions private | `_camelCase` | `_parseRow()` |
| Constants | `SCREAMING_SNAKE` | `MASTER_SHEET_ID` |

### Google Sheets
| Loại | Convention | Ví dụ |
|---|---|---|
| Sheet names | `snake_case` | `keywords`, `silo_structure` |
| Column names | `snake_case` | `keyword_id`, `cluster_group` |
| Primary key | `{entity}_id` | `keyword_id`, `silo_id` |
| Foreign key | `{entity}_id` | `silo_id` (FK → silo_structure) |
| Boolean columns | `is_*` hoặc plain | `gsc_sync_enabled`, `featured_snippet` |
| Date columns | `*_date` / `*_at` | `start_date`, `synced_at` |

### Frontend (Alpine.js + HTML)
| Loại | Convention | Ví dụ |
|---|---|---|
| Alpine x-data props | `camelCase` | `currentProject`, `isLoading` |
| Alpine methods | `camelCase` | `loadKeywords()`, `showModal()` |
| HTML IDs | `kebab-case` | `keyword-table`, `silo-tree` |
| HTML classes (custom) | `ts-` prefix | `ts-card`, `ts-badge-intent` |
| Page files | `{module}.html` | `keywords.html`, `silo.html` |

---

## GAS Best Practices

### Error Handling Pattern
```javascript
// Trong Service.gs
function processKeyword(data) {
  if (!data.keyword) throw new Error('keyword is required');
  if (data.keyword.length > 500) throw new Error('keyword too long');
  // ... logic
}

// Trong API.gs — wrap all handlers
function dispatch(action, params) {
  try {
    const data = handler();
    return { success: true, data };
  } catch (e) {
    Logger.log(`[API] Error in ${action}: ${e.message}`);
    return { success: false, error: e.message };
  }
}
```

### Properties Service Usage
```javascript
// Lưu API keys — KHÔNG hardcode trong code
const PROPS = PropertiesService.getScriptProperties();
const GEMINI_KEY = PROPS.getProperty('GEMINI_API_KEY');
const MASTER_SHEET_ID = PROPS.getProperty('MASTER_SHEET_ID');
```

### Logging Pattern
```javascript
// Format log thống nhất: [Module] Level: message
Logger.log('[KeywordService] INFO: Classifying 50 keywords');
Logger.log('[GSCService] ERROR: Sync failed - ' + error.message);
Logger.log('[AuditService] INFO: Loaded 55 checklist items');
```

---

## Alpine.js Patterns

### State Management
```javascript
// Mỗi page/module = 1 Alpine x-data object
// Global state: app.html (currentProject, user, toast)
// Local state: {module}.html (keywords, filters, pagination)

// Pattern chuẩn cho mọi page
function keywordsPage() {
  return {
    // State
    keywords: [],
    loading: false,
    filters: { cluster: '', intent: '', status: '' },
    page: 1,
    searchQuery: '',
    
    // Lifecycle
    async init() {
      await this.loadKeywords();
    },
    
    // Computed
    get filteredKeywords() { /* ... */ },
    get paginatedKeywords() { /* ... */ },
    get stats() { /* ... */ },
    
    // Actions
    async loadKeywords() { /* ... */ },
    async addKeyword(data) { /* ... */ },
  };
}
```

### Toast Notifications
```javascript
// Global toast (app.html)
this.$dispatch('show-toast', { message: 'Saved!', type: 'success' });
this.$dispatch('show-toast', { message: 'Error', type: 'danger' });
// types: success | danger | warning | neutral
```

---

## HTML/UI Conventions

### Shoelace Components Usage
```html
<!-- Buttons: sl-button với variant chuẩn — label tiếng Anh -->
<sl-button variant="primary">Save</sl-button>
<sl-button variant="default">Cancel</sl-button>
<sl-button variant="danger">Delete</sl-button>

<!-- Table: sl-table hoặc custom table + sl-badge -->
<sl-badge variant="success">active</sl-badge>
<sl-badge variant="warning">paused</sl-badge>

<!-- Modal: sl-dialog — label tiếng Anh -->
<sl-dialog label="Add Keyword" :open="showAddModal">

<!-- Icon: 100% Lucide Icons — KHÔNG dùng emoji/ký tự -->
<i data-lucide="plus" class="w-4 h-4"></i>
<i data-lucide="trash-2" class="w-4 h-4"></i>
<i data-lucide="sparkles" class="w-4 h-4"></i> <!-- AI features -->
```

### Loading States
```html
<!-- Mọi action async phải có loading state -->
<sl-button :loading="loading" @click="loadKeywords()">
  Tải dữ liệu
</sl-button>

<!-- Skeleton loading cho table -->
<template x-if="loading">
  <div class="ts-skeleton-table">...</div>
</template>
```

---

## Quy Tắc Không Được Vi Phạm

1. **KHÔNG hardcode** Spreadsheet IDs, API keys vào code — dùng PropertiesService
2. **KHÔNG gọi Sheet API** nhiều hơn 1 lần trong cùng 1 function nếu có thể tránh
3. **KHÔNG để AI** tự ghi database — luôn có bước human confirm
4. **KHÔNG deploy** trực tiếp từ IDE — test trên GAS editor trước
5. **KHÔNG mix** business logic vào API.gs — chỉ dispatch, logic ở Service
6. **KHÔNG forget** withFailureHandler() khi gọi google.script.run
7. **KHÔNG dùng emoji/ký tự Unicode** làm icon trong UI — chỉ Lucide Icons
8. **KHÔNG dùng tiếng Việt** cho UI text (labels, buttons, headings) — phải là tiếng Anh
9. **KHÔNG tích hợp AI khác** ngoài Gemini (không OpenAI, Claude, v.v.)
10. **KHÔNG dùng dark mode** làm mặc định — light theme là default
