# CORE_ARCHITECTURE — Kiến Trúc Hệ Thống ToolSEO

## Tổng Quan

ToolSEO là **SPA-like application** chạy trên Google Apps Script, sử dụng `HtmlService` để serve frontend và `google.script.run` làm cầu nối RPC bất đồng bộ giữa client và server.

---

## Sơ Đồ Kiến Trúc

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT (Browser)                         │
│                                                              │
│  index.html (SPA Shell)                                      │
│  ├── Top Bar: project switcher, user info                    │
│  ├── Sidebar Nav: Alpine.js x-data router                    │
│  ├── Content Area: dynamic page injection (14 pages)         │
│  └── Globals: modals, toasts, command palette                │
│                                                              │
│  Alpine.js (state) → api-client.html (Promise wrapper)       │
└───────────────────────┬─────────────────────────────────────┘
                        │  google.script.run (async RPC)
┌───────────────────────▼─────────────────────────────────────┐
│                     SERVER (Apps Script)                      │
│                                                              │
│  Code.gs      → doGet() serve HTML, include() partials       │
│  Router.gs    → Page dispatcher                              │
│  API.gs       → Action dispatcher (switch action name)       │
│  Utils.gs     → UUID gen, date format, validation            │
│  SheetDB.gs   → Generic CRUD: getRows, addRow, updateRow     │
│  GSCClient.gs → Google Search Console API v3 wrapper         │
│  GeminiClient.gs → Gemini AI API wrapper (JSON output)       │
│                                                              │
│  services/                                                   │
│  └── *Service.gs → Business logic per module (14 services)   │
└───────────────────────┬─────────────────────────────────────┘
                        │  Sheets API (batch read/write)
┌───────────────────────▼─────────────────────────────────────┐
│  Google Drive Folder (Configured via Settings):              │
│  ├── _ToolSEO_Master                                         │
│  │   └── _projects  (danh sách & metadata tất cả dự án)      │
│  │                                                           │
│  ├── ToolSEO — Project A (1 file spreadsheet):               │
│  │   ├── _config              project settings & brand config│
│  │   ├── keywords             keyword research database      │
│  │   ├── silo_structure       page hierarchy & URLs          │
│  │   ├── content_outlines     outline briefs                 │
│  │   ├── technical_audit      audit checklist (55+ items)    │
│  │   ├── onpage_checklist     on-page SEO per URL            │
│  │   ├── geo_checklist        GEO readiness per URL          │
│  │   ├── backlinks            link building pipeline         │
│  │   ├── rankings             position tracking snapshots    │
│  │   ├── gsc_performance      GSC auto-sync data             │
│  │   ├── gsc_index_status     URL inspection results         │
│  │   ├── rate_card            đơn giá hạng mục               │
│  │   └── quotation            bảng báo giá                   │
│  │                                                           │
│  ├── ToolSEO — Project B                                     │
│  │                                                           │
│  └── /Reports                                                │
│      ├── Report templates (Slides/Docs)                      │
│      └── Generated reports per client                        │
└─────────────────────────────────────────────────────────────┘
```

---

## Data Flow Chi Tiết

### Read Flow (Lấy dữ liệu)
```
1. User action trên UI
2. Alpine.js gọi callServer('keyword.getAll', {projectId})
3. api-client.html bọc thành Promise qua google.script.run
4. API.gs dispatch('keyword.getAll') → KeywordService.getAll(projectId)
5. KeywordService → SheetDB.getRows(spreadsheetId, 'keywords')
6. SheetDB đọc sheet, map header → object array
7. API.gs wrap: {success: true, data: [...]} → callServer resolve(data)
8. Alpine.js update state → UI re-render
```

### Write Flow (Ghi dữ liệu)
```
1. User submit form
2. Alpine.js validate input → callServer('keyword.add', {projectId, data})
3. API.gs dispatch → KeywordService.add(projectId, data)
4. KeywordService validate biz logic → gen UUID → SheetDB.addRow(...)
5. SheetDB append row vào sheet
6. API.gs wrap: {success: true, data: newRow}
7. callServer resolve(newRow) → Alpine.js cập nhật local list
```

### AI Flow (Gemini)
```
1. User click "AI Classify Intent"
2. Alpine.js gửi array keywords → api-client
3. API.gs → AIService.classifyIntentBatch(keywords)
4. AIService build prompt → GeminiClient.generate(prompt)
5. GeminiClient call Gemini API, parse JSON response
6. Return {keyword, intent, confidence}[] 
7. Alpine.js update intent column, show preview → User confirm → Save
```

### GSC Sync Flow
```
1. Trigger: Daily 2AM hoặc manual button
2. GSCService.syncPerformance(projectId)
3. GSCClient.querySearchAnalytics(siteUrl, dateRange, dimensions)
4. Batch write vào gsc_performance sheet (upsert by date+query+page)
5. Enrichment: update keywords.current_position, rankings snapshots
6. Dashboard KPIs auto-refresh
```

---

## Layered Architecture

```
┌────────────────────────────────────┐
│  Presentation Layer                │
│  index.html + Alpine.js + Shoelace │
├────────────────────────────────────┤
│  API Gateway Layer                 │
│  API.gs (action dispatcher)        │
├────────────────────────────────────┤
│  Service Layer (Business Logic)    │
│  *Service.gs (14 modules)          │
├────────────────────────────────────┤
│  Data Access Layer                 │
│  SheetDB.gs (generic CRUD)         │
├────────────────────────────────────┤
│  Infrastructure Layer              │
│  GSCClient.gs · GeminiClient.gs    │
│  Utils.gs · CacheService           │
├────────────────────────────────────┤
│  Data Store                        │
│  Google Sheets · Google Drive      │
└────────────────────────────────────┘
```

---

## Các Ràng Buộc Kỹ Thuật Quan Trọng

### GAS Quotas & Limits
| Limit | Giá trị | Giải pháp |
|---|---|---|
| Execution time | 6 phút/call | Chia batch, dùng trigger |
| URL Fetch | 20,000 calls/ngày | Cache responses |
| Spreadsheet API | 300 read/phút | Batch reads |
| Script triggers | 20 triggers | Tối thiểu hoá số triggers |
| Cache size | 100KB/item | Compress data lớn, split keys |
| Properties storage | 500KB total | Chỉ dùng cho API key, IDs |

### Performance Patterns
- **Batch read**: Đọc toàn sheet 1 lần, filter client-side thay vì nhiều lần API call
- **CacheService**: Cache kết quả `getRows` trong 5 phút cho data ít thay đổi
- **Optimistic updates**: UI update ngay, confirm server background
- **Pagination client-side**: 50 rows/page, toàn bộ data đã tải về

### Security
- GAS Web App chạy với quyền Google account owner
- Không expose credentials trong client code
- API keys (Gemini) lưu trong `PropertiesService.getScriptProperties()`
- GSC token: OAuth managed by GAS tự động
