# REF_AI_HANDOVER — AI Assistant Context Document

> Đọc file này trước khi bắt đầu làm việc trên ToolSEO. Cung cấp đủ context để AI assistant onboard nhanh vào dự án.

---

## Dự Án Là Gì?

**ToolSEO Planner** — Ứng dụng quản lý kế hoạch SEO/GEO cho SEO agency.  
**Không phải** CMS, content tool, hay WordPress plugin.  
**Là**: Planning tool — keyword mapping, silo architecture, content outline, audit checklists, GSC integration, client reporting.

**Tech Stack**: Google Apps Script + Google Sheets + Alpine.js + Tailwind CSS + Shoelace Web Components + Gemini AI + GSC API.

---

## Files Tài Liệu Quan Trọng

| File | Đọc khi nào |
|---|---|
| `CORE_PROJECT.md` | Tổng quan, folder structure, conventions |
| `CORE_ARCHITECTURE.md` | Data flow, layered architecture, constraints |
| `CORE_DATABASE.md` | Schema tất cả Google Sheets |
| `CORE_AI_CONTEXT.md` | Gemini integration, prompt templates |
| `WORKFLOW_OVERVIEW.md` | Luồng nghiệp vụ tổng thể (10 bước) |
| `ARCH_PATTERNS.md` | SheetDB, Service, API dispatcher patterns |
| `ARCH_INTEGRATIONS.md` | GSC API, Drive, Slides code examples |
| `ARCH_PERFORMANCE.md` | GAS quotas, batch patterns, caching |
| `GOV_CODING_STANDARDS.md` | Naming, error handling, Alpine.js conventions |
| `GOV_UX_GUIDELINES.md` | Shoelace components, layout, UX micro-copy |
| `MODULE_*.md` | Chi tiết từng module (xem khi làm việc module đó) |
| `CORE_FEATURES.md` | Danh sách features theo phase/priority |
| `TRACK_ROADMAP.md` | Trạng thái hiện tại các phase |
| `TRACK_DECISIONS.md` | Architecture Decision Records (ADR) |

---

## Kiến Trúc Nhanh (TL;DR)

```
Browser (Alpine.js SPA)
    → google.script.run (async RPC)
    → API.gs (dispatcher)
    → *Service.gs (business logic)
    → SheetDB.gs (CRUD)
    → Google Sheets (database)

External APIs:
    → GeminiClient.gs → Gemini 2.0 Flash (AI)
    → GSCClient.gs → Search Console API (SEO data)
    → DriveApp/SlidesApp/DocsApp → Reports, Templates
```

---

## Rules Không Được Vi Phạm

1. **AI luôn cần human review** — không auto-apply AI output vào database
2. **Không hardcode** secrets/IDs — dùng `PropertiesService.getScriptProperties()`
3. **Batch read** Google Sheets — không đọc nhiều lần trong 1 function
4. **JSON output** từ Gemini — parse ngay trong `GeminiClient.gs`
5. **Error wrap** mọi `google.script.run` call — `.withFailureHandler()`
6. **Service layer tách biệt** — mỗi `*Service.gs` chỉ xử lý module của mình

---

## Patterns Code Phải Tuân Theo

### Service Pattern
```javascript
const KeywordService = {
  getAll(projectId) { /* ... */ },
  add(projectId, data) { /* ... */ },
};
```

### API Dispatcher
```javascript
// API.gs
function dispatch(action, params) {
  const handlers = { 'keyword.getAll': () => KeywordService.getAll(params.projectId) };
  try { return { success: true, data: handlers[action]() }; }
  catch(e) { return { success: false, error: e.message }; }
}
```

### Alpine.js API Call
```javascript
const data = await callServer('keyword.getAll', { projectId: this.currentProject });
// callServer là wrapper Promise cho google.script.run (xem api-client.html)
```

---

## Module → File Mapping

| Module | Sheet | Service File | Page File |
|---|---|---|---|
| M1 Project | `_projects` | `ProjectService.gs` | `projects.html` |
| M2 Keywords | `keywords` | `KeywordService.gs` | `keywords.html` |
| M3 Silo | `silo_structure` | `SiloService.gs` | `silo.html` |
| M4 Outline | `content_outlines` | `OutlineService.gs` | `outlines.html` |
| M5 Tech Audit | `technical_audit` | `AuditService.gs` | `audit.html` |
| M6 On-Page | `onpage_checklist` | `OnPageService.gs` | `onpage.html` |
| M7 GEO | `geo_checklist` | `GeoService.gs` | `geo.html` |
| M8 Backlinks | `backlinks` | `BacklinkService.gs` | `backlinks.html` |
| M9 Rankings | `rankings` | `RankingService.gs` | `rankings.html` |
| M10 GSC | `gsc_performance`, `gsc_index_status` | `GSCService.gs` | `gsc.html` |
| M11 Dashboard | (aggregated) | `DashboardService.gs` | `dashboard.html` |
| M12 Reports | `_reports` | `ReportService.gs` | `reports.html` |
| M13 Quotation | `rate_card`, `quotation` | `QuotationService.gs` | `quotation.html` |
| M14 AI | (no sheet) | `AIService.gs` | (inline in modules) |

---

## Development Phase Hiện Tại

Xem `TRACK_ROADMAP.md` để biết phase hiện tại. Mặc định: **Phase 1 — Core MVP**.

**Để bắt đầu làm việc**:
1. Đọc `TRACK_ROADMAP.md` → xác định task hiện tại
2. Đọc `MODULE_*.md` tương ứng với task
3. Follow patterns trong `ARCH_PATTERNS.md`
4. Xem schema trong `CORE_DATABASE.md`
5. Tuân theo conventions trong `GOV_CODING_STANDARDS.md`
