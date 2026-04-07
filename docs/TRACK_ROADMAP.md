# TRACK_ROADMAP — Lộ Trình & Trạng Thái Phát Triển

> Cập nhật file này sau mỗi milestone hoàn thành.

---

## Tổng Quan 5 Phases

```
Phase 1 [Core MVP]        → 🟡 Đang phát triển
Phase 2 [Agency Ops]      → ⬜ Chưa bắt đầu
Phase 3 [Data & GSC]      → ⬜ Chưa bắt đầu
Phase 4 [Polish]           → ⬜ Chưa bắt đầu
Phase 5 [WP Integration]  → ⬜ Chưa bắt đầu
```

---

## Phase 1 — Core MVP (P0: Critical)

**Mục tiêu**: Planner có thể tạo project, nghiên cứu keywords, xây silo, tạo outline, và xem dashboard cơ bản.

### Milestone 1.1 — Foundation & Project Management
- [x] Setup GAS project + clasp CLI
- [x] `appsscript.json` với đủ scopes
- [x] `Code.gs` — doGet(), include(), initConfig()
- [x] `Utils.gs` — uuid(), formatDate(), validators
- [x] `SheetDB.gs` — getRows(), addRow(), updateRow(), batchAddRows()
- [x] `API.gs` — dispatch() + error wrapper
- [x] Master Sheet `_projects` với đúng headers
- [x] Project Template Sheet (14 tabs với headers theo CORE_DATABASE.md)
- [x] `ProjectService.gs` — CRUD + create (auto-tạo sheet từ template)
- [x] `SeedService.gs` — Demo seed data cho tất cả 12 sheets (M2-M13)
- [x] `html/index.html` — SPA shell (top bar, sidebar, content area)
- [x] `html/js/app.html` — Alpine.js router + global state
- [x] `html/js/api-client.html` — callServer() Promise wrapper
- [x] `html/pages/projects.html` — project list + create form
- [x] `html/css/styles.html` — Tailwind overrides, custom classes

### Milestone 1.2 — Keyword Research
- [x] `KeywordService.gs` — getAll, bulkImport, update, mapToSilo, detectCannibalization
- [x] `html/pages/keywords.html` — smart table, filters, stats cards
- [x] CSV import: paste textarea + parse + preview + confirm
- [x] Intent badges (4 màu)
- [x] Mapping panel (right sidebar)
- [x] Cannibalization highlight
- [x] AI Classify Intent (preview-first pattern)

### Milestone 1.3 — Silo Architecture
- [ ] `SiloService.gs` — getTree, addNode, moveNode, updateMetadata
- [ ] Tree view component (Alpine.js recursive)
- [ ] Drag-drop reorder
- [ ] Edit panel (right sidebar)
- [ ] Internal link matrix view
- [ ] Export JSON/Markdown

### Milestone 1.4 — Content Outline
- [ ] `OutlineService.gs` — generate, batchGenerate, updateStatus, exportMarkdown
- [ ] `AIService.gs` — generateOutline() → Gemini
- [ ] Outline builder UI (drag-drop sections)
- [ ] Status workflow (draft → reviewed → approved → sent)
- [ ] Export: Markdown + Google Doc auto-create

### Milestone 1.5 — Template System
- [ ] `templates/technical_audit_checklist.json` (55 items)
- [ ] `templates/geo_checklist.json` (25 items)
- [ ] `templates/onpage_checklist.json` (30 items)
- [ ] `templates/content_outline_template.json`
- [ ] `templates/silo_template.json`

### Milestone 1.6 — Basic Dashboard
- [ ] `DashboardService.gs` — getMetrics() aggregate
- [ ] `html/pages/dashboard.html` — 11 KPI cards + 8 Chart.js

---

## Phase 2 — Agency Operations (P1: High)

**Mục tiêu**: Agency có thể làm audit đầy đủ, tạo báo cáo KH, và generate báo giá tự động.

### Milestone 2.1 — Audit Checklists
- [ ] `AuditService.gs` — loadTemplate, update, createSnapshot
- [ ] `OnPageService.gs` — generateForUrl, update, getScore
- [ ] `GeoService.gs` — generateForUrl, update
- [ ] `AIService.gs` — summarizeAuditFindings, scoreGeoContent
- [ ] `html/pages/audit.html` — checklist grouped by category
- [ ] `html/pages/onpage.html` — per-page checker + score comparison
- [ ] `html/pages/geo.html` — radar chart + AI visibility log

### Milestone 2.2 — Client Reporting
- [ ] `ReportService.gs` — generateMonthlyReport, generateStatusUpdate
- [ ] Report template Slides/Docs design
- [ ] `AIService.gs` — generateReportNarrative
- [ ] `html/pages/reports.html` — config + generate + history

### Milestone 2.3 — Quotation Engine
- [ ] `QuotationService.gs` — calculate, save, exportToSheet
- [ ] `html/pages/quotation.html` — rate card + auto-quote + export

### Milestone 2.4 — Gemini AI Copilot (Full)
- [ ] `AIService.gs` — expandLSI, suggestClusters, suggestSilo
- [ ] AI buttons tích hợp vào tất cả modules
- [ ] AI preview panel (review-before-apply)

### Bổ Sung Phase 2
- [ ] Content Calendar view (per silo)
- [ ] Activity Log (audit trail)

---

## Phase 3 — Data & GSC (P2: Medium)

### Milestone 3.1 — GSC Integration
- [ ] `GSCClient.gs` — querySearchAnalytics, inspectUrl, listSitemaps
- [ ] `GSCService.gs` — syncPerformance, batchInspectUrls
- [ ] Time-based triggers setup
- [ ] `html/pages/gsc.html` — GSC Dashboard
- [ ] Enrichment: keywords.current_position, silo index badge

### Milestone 3.2 — Ranking Tracker
- [ ] `RankingService.gs` — snapshot, computeChanges, importCSV
- [ ] `html/pages/rankings.html` — trend charts, movers, distribution

### Milestone 3.3 — Backlink Tracker
- [ ] `BacklinkService.gs` — CRUD, bulkImport
- [ ] `html/pages/backlinks.html` — Kanban pipeline view

### Milestone 3.4 — Advanced Dashboard
- [ ] Advanced Chart.js: tất cả 8 charts
- [ ] Competitor tracker (stub)

---

## Phase 4 — Polish (P3: Nice-to-have)
- [ ] Dark / Light mode toggle
- [ ] White-label settings (logo, brand color)
- [ ] Notes & Annotations on Ranking chart
- [ ] Enhanced PDF export

---

## Phase 5 — WP Bridge Plugin (Vision)
- [ ] WP Bridge Plugin codebase
- [ ] Webhook: ToolSEO → WP
- [ ] Status callback: WP → ToolSEO
- [ ] Outline sync flow
- [ ] Brand config propagation

---

## Changelog Nhanh

| Date | Version | Ghi chú |
| 2026-04-07 | v0.4.1 | Nâng cấp AI Classify Intent (Auto-chunking) và Table Sorting (M2) |
| 2026-04-07 | v0.4.0 | Hoàn thiện toàn bộ M2 (Keyword Research & Mapping) |
| 2026-04-07 | v0.3.0 | Thêm màn hình quản lý Projects và tối ưu Responsive UI |
| 2026-04-07 | v0.2.1 | Chuyển sang kiến trúc Google Drive Folder, sửa lỗi Seed data |
| 2026-04-05 | v0.2.0 | Hoàn thiện GUI Shell, API Dispatcher & Config Setup |
| 2026-04-05 | v0.1.0 | Khởi tạo tài liệu dự án |

> Xem chi tiết: `TRACK_CHANGELOG.md`
