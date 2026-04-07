# CORE_PROJECT — Tổng Quan Dự Án ToolSEO

## Mục Tiêu

**ToolSEO Planner** là ứng dụng quản lý kế hoạch SEO/GEO dành cho SEO Planner tại agency. Không viết content, chỉ **lập kế hoạch, cấu trúc silo, keyword mapping, audit checklist, outline mẫu** và export deliverables cho team content.

**Triết lý cốt lõi**: Planning-first · Template-driven · Checklist-based

---

## Tech Stack (100% Free, All CDN)

| Layer | Technology | Ghi chú |
|---|---|---|
| Database | Google Sheets | Schema định sẵn per-project |
| Backend | Google Apps Script (V8) | doGet(), service layer |
| Hosting | GAS Web App (HtmlService) | URL public cố định |
| Storage | Google Drive | Template, export files |
| UI | Shoelace 2.x | Web components CDN |
| CSS | Tailwind CSS 3.x (Play CDN) | Utility-first |
| Reactivity | Alpine.js 3.x | SPA routing, state |
| Icons | Lucide Icons | CDN |
| Charts | Chart.js 4.x | Dashboard visualizations |
| API | Google Search Console API v3 | GSC data engine |
| AI | Gemini 2.0 Flash (free tier) | JSON-structured output |

---

## Folder Structure

```
toolseo/
├── appsscript.json
├── Code.gs              # doGet(), include()
├── Router.gs            # Page routing
├── API.gs               # Action dispatcher
├── Utils.gs             # UUID, date, helpers
├── SheetDB.gs           # Generic CRUD layer
├── GSCClient.gs         # GSC API wrapper
├── GeminiClient.gs      # Gemini API wrapper
│
├── services/
│   ├── ProjectService.gs
│   ├── KeywordService.gs
│   ├── SiloService.gs
│   ├── OutlineService.gs
│   ├── AuditService.gs
│   ├── BacklinkService.gs
│   ├── RankingService.gs
│   ├── GSCService.gs
│   ├── GeoService.gs
│   ├── OnPageService.gs
│   ├── DashboardService.gs
│   ├── ReportService.gs
│   ├── QuotationService.gs
│   └── AIService.gs
│
├── html/
│   ├── index.html       # SPA shell
│   ├── css/styles.html
│   ├── js/
│   │   ├── app.html          # Alpine.js router
│   │   ├── api-client.html   # google.script.run wrapper
│   │   └── components.html   # Shoelace wrappers
│   └── pages/           # 14 module pages
│
├── templates/           # JSON/CSV import templates
└── docs/                # Tài liệu dự án (file này)
```

---

## Conventions

### Naming
- **Files GAS**: `PascalCase.gs` (tất cả — Services, Utils, Clients)
- **Sheet names**: `snake_case` (vd: `keywords`, `silo_structure`)
- **Column IDs**: `snake_case`, primary key = `{entity}_id`
- **Alpine.js data**: `camelCase`
- **CSS classes**: Tailwind utilities + prefix `ts-` cho custom classes

### Data Flow
```
User Action → Alpine.js → api-client.html → google.script.run
    → API.gs dispatcher → Service.gs → SheetDB.gs → Google Sheets
    ← JSON response ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ←
```

### Google Sheets Structure
- **Master Sheet**: `_projects` (danh sách tất cả dự án)
- **Per-Project Sheet**: Tạo tự động từ template khi tạo project mới
  - Mỗi project = 1 Google Sheet file riêng
  - Lưu `spreadsheet_id` trong `_projects`

### Authentication
- **Single-user**: Google account owner của GAS project
- **GAS Web App**: Execute as "Me", Access "Anyone with link"
- **GSC API**: OAuth scope `webmasters.readonly`

---

## Quy Tắc Phát Triển

1. **Không deploy khi chưa test** trên GAS IDE
2. **Output AI phải dạng JSON** — parse ngay tại `GeminiClient.gs`
3. **Batch operations > Single operations** cho Sheet reads/writes
4. **Cache kết quả** với `CacheService` khi có thể (TTL 300s)
5. **Error handling** — wrap mọi `google.script.run` với `.withFailureHandler()`
6. **Module tách biệt** — mỗi Service.gs chỉ xử lý logic module của mình
