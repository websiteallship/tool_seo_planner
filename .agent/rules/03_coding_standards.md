# Rule: Coding Standards & Conventions

## 4 Nguyên Tắc Bắt Buộc

| # | Nguyên tắc | Quy tắc |
|---|---|---|
| 1 | Ngôn ngữ UI | **Tiếng Anh** — labels, buttons, headings, placeholders, table headers |
| 2 | Ngôn ngữ code | Comments/notes/giải thích trong `.gs` và `.html` → **ưu tiên tiếng Việt** |
| 3 | Icons | **100% Lucide Icons** `<i data-lucide="name">` — NGHIÊM CẤM emoji/ký tự Unicode làm icon |
| 4 | AI Provider | **Chỉ Gemini** (`gemini-2.0-flash`) — không OpenAI, Claude, hay AI API khác |
| 5 | Default Theme | **Light theme** (`bg-white / slate-50`) — không dark mode mặc định |

---

## UI Components & Patterns

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

<!-- Icon: 100% Lucide Icons — KHÔNG dùng emoji/ký tự Unicode -->
<i data-lucide="plus" class="w-4 h-4"></i>
<i data-lucide="trash-2" class="w-4 h-4"></i>
<i data-lucide="sparkles" class="w-4 h-4"></i> <!-- AI features -->
```

## Naming Conventions

### GAS (Server-side)
| Loại | Convention | Ví dụ |
|---|---|---|
| Service files | `PascalCase.gs` | `KeywordService.gs` |
| Utility/client files | `PascalCase.gs` | `SheetDB.gs`, `GeminiClient.gs` |
| Public functions | `camelCase` | `getAll()`, `addKeyword()` |
| Private functions | `_camelCase` | `_parseRow()`, `_buildPrompt()` |
| Constants | `SCREAMING_SNAKE` | `MASTER_SHEET_ID`, `CACHE_TTL` |

### Google Sheets
| Loại | Convention | Ví dụ |
|---|---|---|
| Sheet names | `snake_case` | `keywords`, `silo_structure` |
| Column names | `snake_case` | `keyword_id`, `cluster_group` |
| Primary key | `{entity}_id` | `keyword_id`, `silo_id` |
| Foreign key | `{entity}_id` | `silo_id` (FK → silo_structure) |
| Boolean columns | `is_*` or plain | `gsc_sync_enabled`, `featured_snippet` |
| Date columns | `*_date` / `*_at` | `start_date`, `synced_at` |

### Frontend (Alpine.js + HTML)
| Loại | Convention | Ví dụ |
|---|---|---|
| Alpine x-data props | `camelCase` | `currentProject`, `isLoading` |
| Alpine methods | `camelCase` | `loadKeywords()`, `showModal()` |
| HTML IDs | `kebab-case` | `keyword-table`, `silo-tree` |
| Custom CSS classes | `ts-` prefix | `ts-card`, `ts-badge-intent` |
| Page files | `{module}.html` | `keywords.html`, `silo.html` |

## Error Handling Pattern

```javascript
// Service.gs — validate input
function processKeyword(data) {
  if (!data.keyword) throw new Error('keyword is required');
  if (data.keyword.length > 500) throw new Error('keyword too long');
}

// API.gs — wrap all handlers
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

## Logging Format

```javascript
// [Module] LEVEL: message
Logger.log('[KeywordService] INFO: Classifying 50 keywords');
Logger.log('[GSCService] ERROR: Sync failed - ' + error.message);
Logger.log('[AuditService] INFO: Loaded 55 checklist items');
```

## Properties Service (API Keys & Config)

```javascript
// KHÔNG hardcode — luôn dùng PropertiesService
const PROPS = PropertiesService.getScriptProperties();
const GEMINI_KEY = PROPS.getProperty('GEMINI_API_KEY');
const MASTER_SHEET_ID = PROPS.getProperty('MASTER_SHEET_ID');
```

## Alpine.js State Pattern

```javascript
// Cấu trúc chuẩn cho mỗi page
function keywordsPage() {
  return {
    // State
    keywords: [], loading: false, page: 1, searchQuery: '',
    filters: { cluster: '', intent: '', status: '' },
    // Lifecycle
    async init() { await this.loadKeywords(); },
    // Computed
    get filteredKeywords() { /* filter logic */ },
    get paginatedKeywords() { return this.filteredKeywords.slice((this.page-1)*50, this.page*50); },
    // Actions
    async loadKeywords() {
      this.loading = true;
      try { this.keywords = await callServer('keyword.getAll', { projectId: this.currentProject }); }
      catch (e) { this.$dispatch('show-toast', { message: e.message, type: 'danger' }); }
      finally { this.loading = false; }
    }
  };
}
```

## Toast Notifications

```javascript
// types: success | danger | warning | neutral
this.$dispatch('show-toast', { message: 'Đã lưu thành công', type: 'success' });
this.$dispatch('show-toast', { message: 'Lỗi kết nối', type: 'danger' });
```

## Quy Tắc KHÔNG Được Vi Phạm

1. **KHÔNG hardcode** Spreadsheet IDs, API keys — dùng PropertiesService
2. **KHÔNG gọi Sheet API** nhiều hơn 1 lần trong 1 function nếu tránh được
3. **KHÔNG để AI** tự ghi database — luôn có bước human confirm trước
4. **KHÔNG deploy** trực tiếp — test trên GAS editor trước
5. **KHÔNG mix** business logic vào API.gs — chỉ dispatch, logic ở Service
6. **KHÔNG forget** `.withFailureHandler()` khi gọi `google.script.run`
7. **KHÔNG dùng emoji/ký tự Unicode** làm icon trong UI — chỉ Lucide Icons
8. **KHÔNG dùng tiếng Việt** cho UI text (labels, buttons, headings) — phải là tiếng Anh
9. **KHÔNG tích hợp AI API khác** ngoài Gemini — không OpenAI, Claude, v.v.
10. **KHÔNG dùng dark mode** làm mặc định — light theme là default
