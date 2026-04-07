# Rule: Module Reference — Features & Business Logic

## Phase 1 — Core MVP (P0 Critical)

### M1 · Project Management
- CRUD dự án SEO (create/view/edit/delete)
- Auto-tạo Google Sheet từ template khi tạo project
- Project switcher top bar (load data theo project)
- Duplicate project (clone settings, tạo sheet mới)
- Archive: set status='archived', không hiện trong list, sheet vẫn tồn tại
- Status badges: active / paused / completed / archived

### M2 · Keyword Research & Mapping
- Bulk import CSV (paste hoặc upload)
- Smart table: sort, filter, search, pagination (50/page)
- Cluster view: group by topic cluster (collapsible)
- Intent badges: color-coded (info/commercial/transactional/nav)
- Mapping panel: gán keyword → URL từ silo (update FK silo_id)
- **Cannibalization detector**: highlight 2+ keywords cùng URL + intent
- Stats cards: tổng, mapped %, phân bổ intent (pie chart)
- Export CSV

### M3 · Silo Architecture & URL Structure
- Tree view: hierarchy trực quan (sl-tree, collapse/expand)
- Drag-drop reorder / thay đổi parent (update parent_id + level)
- Quick add node con
- Internal link matrix: links in/out per page
- Bulk edit meta title/description
- Export silo map (JSON/PNG)
- Import từ template mẫu

### M4 · Content Outline Generator
- Outline builder: visual heading hierarchy editor
- Auto-populate từ silo + keyword khi tạo mới
- Preview mode: render readable document
- Template system: load outline template sẵn
- Export: Markdown, JSON, Google Doc auto-create
- Status workflow: Draft → Reviewed → Approved → Sent
- Batch create outline cho toàn silo

### M11 · Basic Dashboard
- KPI cards: Clicks, Impressions, Keywords, Silo %, Outline status
- Quick actions: Sync GSC, Run Audit, Export summary

---

## Phase 2 — Agency Ops (P1 High)

### M5 · Technical Audit Checklist
- Load template 55+ items (7 categories)
- Progress dashboard: % hoàn thành, phân loại severity
- Filter/group: category, severity, status
- Assign & schedule: team member + due date
- Evidence: link screenshot, PageSpeed report
- Re-audit: reset checklist định kỳ
- AI: auto-tóm tắt findings & recommend fix

### M6 · On-Page SEO Checklist
- Auto-generate per-page checklist từ silo
- Score card: On-page SEO score per page
- Export: per-page report

### M7 · GEO Optimization Checklist
- Per-page GEO score (radar chart 4 categories)
- AI Visibility log, Prompt testing log
- AI: chấm điểm GEO content

### M12 · Client Reporting
- Auto-generate monthly SEO report (Google Slides)
- Auto-generate status update (Google Doc)
- AI: tóm tắt insights, narrative cho báo cáo
- Merge data thực tế vào `{{placeholder}}`

### M13 · Quotation & Pricing Engine
- Rate card CRUD (per hạng mục)
- Auto-calculate từ số lượng trang silo
- Export Google Sheet / PDF
- Lưu lịch sử báo giá per project (versioned)

### M14 · Gemini AI Assistant (Copilot)
- AI Classify Intent (batch)
- AI Suggest Clusters & Silo structure
- AI Generate Outline chuẩn SEO
- AI Expand LSI & PAA keywords
- AI tóm tắt Report & recommend audit fixes

---

## Phase 3 — Data & GSC (P2 Medium)

### M10 · Google Search Console Integration
- OAuth setup + connection GSC property
- Auto-sync performance data (Daily 2AM trigger)
- URL Inspection batch (top 100 URLs, weekly)
- Sitemap check tự động
- GSC Dashboard: Clicks, Impressions, CTR, Position
- Enrich Keyword module: update current_position
- Enrich Silo module: index badge per page

### M9 · Ranking Tracker
- GSC auto-sync position snapshots
- Manual input + CSV import (Ahrefs/SEMrush)
- Trend chart per keyword
- Movers: top gainers/losers
- Distribution: Top 3 / 4-10 / 11-20 / 50+
- SERP features: Featured Snippet, AI Overview tracker

### M8 · Backlink Tracker
- Pipeline Kanban: prospect → contacted → negotiating → live → lost
- Bulk import CSV từ Ahrefs
- Anchor text distribution chart
- DA histogram
- Lost link alerts

---

## Template Files (templates/)

| File | Items |
|---|---|
| `technical_audit_checklist.json` | 55+ items, 7 categories |
| `geo_checklist.json` | 25 items |
| `onpage_checklist.json` | 30 items |
| `content_outline_template.json` | Outline structure |
| `silo_template.json` | Sample silo hierarchy |
