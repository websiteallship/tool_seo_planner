# CORE_FEATURES — Danh Sách Tính Năng ToolSEO

> Tài liệu gốc: `docs/core/features_summary.md` | Cập nhật theo phân tích ưu tiên & roadmap triển khai.

---

## Lộ Trình Phát Triển (5 Phases)

| Phase | Tên | Mức ưu tiên | Trạng thái |
|---|---|---|---|
| Phase 1 | Core MVP | P0 — Critical | 🟡 Đang phát triển |
| Phase 2 | Agency Operations | P1 — High | ⬜ Chưa bắt đầu |
| Phase 3 | Data & GSC | P2 — Medium | ⬜ Chưa bắt đầu |
| Phase 4 | Polish | P3 — Nice-to-have | ⬜ Chưa bắt đầu |
| Phase 5 | WP Integration | P5 — Vision | ⬜ Chưa bắt đầu |

---

## Phase 1 — Core MVP (P0: Critical)

### M1 · Project Management
- [ ] Tạo / xem / sửa / xóa dự án SEO
- [ ] Auto-tạo Google Sheet từ template khi tạo project mới
- [ ] Project switcher trên top bar
- [ ] Duplicate project (clone settings)
- [ ] Status badges: active / paused / completed / archived

### M2 · Keyword Research & Mapping
- [x] Bulk import keywords từ CSV (paste hoặc upload)
- [x] Smart table: sort, filter, search, pagination
- [x] Cluster view: group by topic cluster (collapsible)
- [x] Intent badges: color-coded (info / commercial / transactional / nav)
- [x] Mapping panel: gán keyword → URL từ silo
- [x] Cannibalization detector: highlight 2+ keywords cùng URL + intent
- [x] Stats cards: tổng, mapped %, phân bổ intent (pie chart)

### M3 · Silo Architecture & URL Structure
- [ ] Tree view: hierarchy trực quan (collapse/expand)
- [ ] Drag-drop reorder / thay đổi parent
- [ ] Quick add node con
- [ ] Internal link matrix: table links in/out
- [ ] Bulk edit meta title/description
- [ ] Export silo map (JSON/PNG)
- [ ] Import từ template mẫu

### M4 · Content Outline Generator
- [ ] Outline builder: visual heading hierarchy editor
- [ ] Auto-populate từ silo + keyword khi tạo mới
- [ ] Preview mode: render readable document
- [ ] Template system: load outline template sẵn
- [ ] Export: Markdown, JSON, Google Doc auto-create
- [ ] Status workflow: Draft → Reviewed → Approved → Sent
- [ ] Batch create outline cho toàn silo

### M11 · Basic Dashboard
- [ ] KPI cards: Clicks, Impressions, Keywords, Silo %, Outline status
- [ ] Quick actions: Sync GSC, Run Audit, Export summary

### Template System
- [ ] `technical_audit_checklist.json` (55+ items)
- [ ] `geo_checklist.json` (25 items)
- [ ] `onpage_checklist.json` (30 items)
- [ ] `content_outline_template.json`
- [ ] `silo_template.json`

---

## Phase 2 — Agency Operations (P1: High)

### M5 · Technical Audit Checklist
- [ ] Load template 55+ checklist items (7 categories)
- [ ] Progress dashboard: % hoàn thành, phân loại severity
- [ ] Filter/group: by category, severity, status
- [ ] Assign & schedule: gán team member + due date
- [ ] Evidence: link screenshot, PageSpeed report
- [ ] Re-audit: reset checklist định kỳ
- [ ] AI: auto-tóm tắt findings & recommend fix

### M6 · On-Page SEO Checklist
- [ ] Auto-generate per-page checklist từ silo
- [ ] Score card: On-page SEO score per page
- [ ] Comparison view: so sánh score nhiều pages
- [ ] Export: per-page report

### M7 · GEO Optimization Checklist
- [ ] Per-page GEO score (radar chart 4 categories)
- [ ] AI Visibility log: track citation checks
- [ ] Prompt testing log
- [ ] AI: chấm điểm GEO content

### M12 · Client Reporting System
- [ ] Auto-generate monthly SEO report (Google Slides)
- [ ] Auto-generate status update (Google Doc)
- [ ] AI: tóm tắt insights, narrative cho báo cáo
- [ ] Merge data thực tế vào template placeholders

### M13 · Quotation & Pricing Engine
- [ ] Rate card management (per hạng mục)
- [ ] Auto-calculate từ số lượng trang silo
- [ ] Render báo giá → export Google Sheet / PDF
- [ ] Lưu lịch sử báo giá per project

### M14 · Gemini AI Assistant (Copilot)
- [ ] AI Classify Intent: phân loại keyword intent hàng loạt
- [ ] AI Suggest Clusters & Silo structure
- [ ] AI Generate Outline chuẩn SEO
- [ ] AI Expand LSI & PAA keywords
- [ ] AI tóm tắt Report & recommend audit fixes

### Bổ sung Phase 2
- [ ] Content Calendar: lịch deadline content per silo
- [ ] Activity Log: audit trail toàn bộ thay đổi

---

## Phase 3 — Data & GSC (P2: Medium)

### M10 · Google Search Console Integration
- [ ] OAuth setup + connection GSC property
- [ ] Auto-sync performance data hàng ngày (Daily trigger 2AM)
- [ ] URL Inspection batch hàng tuần (top 100 URLs)
- [ ] Sitemap check tự động
- [ ] GSC Dashboard: Clicks, Impressions, CTR, Position
- [ ] Enrich Keyword module với GSC data
- [ ] Enrich Silo module: index badge per page

### M9 · Ranking Tracker
- [ ] GSC auto-sync position snapshots
- [ ] Manual input ranking
- [ ] CSV import từ Ahrefs/SEMrush
- [ ] Trend chart per keyword
- [ ] Movers: top gainers/losers
- [ ] Distribution chart: Top 3 / 4-10 / 11-20 / 50+
- [ ] SERP features tracker: Featured Snippet, AI Overview

### M8 · Backlink Tracker
- [ ] Pipeline view: Kanban stages
- [ ] Bulk import CSV từ Ahrefs
- [ ] Anchor text distribution chart
- [ ] DA histogram
- [ ] Lost link alerts

### Advanced Dashboard
- [ ] Chart.js: tất cả 8 charts
- [ ] Competitor Tracker: keyword/content gap

---

## Phase 4 — Polish (P3: Nice-to-have)

- [ ] Notes & Annotations trên Ranking chart (algorithm update markers)
- [ ] Dark / Light mode toggle
- [ ] White-label settings (logo, màu sắc)
- [ ] Enhanced export: PDF, ZIP delivery package

---

## Phase 5 — WP Bridge Plugin (Vision)

### Kiến trúc Headless
- **ToolSEO (GAS)**: Planning & Brief center — Single Source of Truth
- **WP Bridge Plugin**: Execution layer (stateless, chỉ nhận lệnh)

### Flow
```
ToolSEO → Approve Outline JSON → WP Bridge API
    → Queue Management → LLM API generate content
    → WordPress publish → Webhook status báo về
    → ToolSEO update trạng thái + SEO Score
```

### Features WP Bridge
- [ ] Outline Sync: đẩy outline JSON từ GAS → WP
- [ ] Webhook Receiver: nhận status update từ WP
- [ ] Sync Calendar: đồng bộ content calendar
- [ ] Brand Config: Brand persona, tone, blacklist từ ToolSEO _config
