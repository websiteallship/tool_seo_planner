# MODULE_AUDIT — Technical Audit + On-Page + GEO Checklist (M5+M6+M7)

## Logic & Workflow Nghiệp Vụ

### Technical Audit (M5)

#### 1. Khởi Tạo Audit
```
Planner click "Load Audit Template"
  → AuditService.loadTemplate(projectId):
      Import 55+ items từ templates/technical_audit_checklist.json
      Batch write vào technical_audit sheet
  → Hiển thị checklist grouped by category (7 nhóm)
  → Progress: "0/55 completed"
```

#### 2. Thực Hiện Audit
```
Planner check từng item:
  → Inspect trực tiếp trên website (tool bên ngoài: GSC, PageSpeed, Screaming Frog)
  → Quay lại app → đổi status: not_checked → pass/fail/na
  → Nếu fail: điền details (findings) + recommendation
  → Attach evidence: link screenshot hoặc PageSpeed URL
  → Assign item cho team member + set due_date
```

#### 3. AI Summarize Audit
```
Planner click "AI Summarize Findings"
  → AIService nhận tất cả items có status=fail + details
  → Gemini trả:
      executive_summary (3-5 câu)
      top_fixes [{issue, recommendation, priority, effort}]
      quick_wins []
  → Render summary panel → Export PDF/Markdown
```

#### 4. Re-Audit (Periodic)
```
Planner click "Re-audit" → Confirm
  → Reset status = "not_checked" cho tất cả items (giữ history)
  → Tạo snapshot: copy toàn bộ sheet sang tab "audit_YYYY-MM-DD"
  → Bắt đầu audit mới
```

---

### On-Page Checklist (M6)

#### Workflow
```
Planner chọn URL từ dropdown (silo_structure pages)
  → Nếu chưa có checklist: auto-generate 30 items từ template
  → Planner review từng mục:
      Check current_value: lấy từ website thực tế
      Đặt status: pass / fail / na
  → Score tự tính: count(pass) / count(!na) × 100%
  → View "Score Comparison": bảng so sánh score nhiều pages
```

**Quy tắc**: 1 URL = 1 instance checklist. Nếu check lại URL đã có → update, không tạo mới.

---

### GEO Checklist (M7)

#### Workflow
```
Planner chọn URL (ưu tiên pillar pages)
  → Load 25 GEO checklist items (4 categories)
  → [Optional] AI Score: gửi content/outline của page → Gemini
      Nhận: {total_score, dimensions: {answer_first, structure, authority, fact_density},
             improvements[], critical_fixes[]}
  → Planner update từng item status
  → Log AI Visibility: ghi nhận kết quả kiểm tra thủ công trên ChatGPT/Gemini/Perplexity
  → Log Prompt Testing: ghi câu query đã test + kết quả
```

---

## Checklist Templates

### Technical Audit (55+ items — 7 categories)
```
📁 Crawlability (8):      robots.txt, sitemap, crawl budget, orphaned pages, redirect chains...
📁 Indexability (7):      canonical, meta robots, duplicate content, 4xx/5xx, index coverage...
📁 Performance (8):       LCP <2.5s, INP <200ms, CLS <0.1, images, minification, TTFB...
📁 Mobile (5):            mobile-first, viewport, tap targets, font size, content parity...
📁 Security (4):          HTTPS, mixed content, HSTS, malware scan...
📁 Schema (6):            JSON-LD, Organization, Article, FAQ, Product, Rich Results Test...
📁 Architecture (6):      breadcrumbs, URL structure, navigation, 404, sitemap HTML...
📁 AI/GEO Readiness (8):  GPTBot access, answer-first, entity clarity, freshness, TL;DR...
```

### On-Page (30 items — 8 categories)
```
📁 Title & Meta (5) | Headings (4) | Content Quality (6) | Images (4)
📁 Internal Links (4) | Schema (3) | UX Signals (4)
```

### GEO (25 items — 4 categories)
```
📁 Technical Accessibility (5): robots.txt AI crawlers, schema, semantic HTML
📁 Content Structure / Answer-First (8): direct answer <120 words, question H2s, TL;DR...
📁 Authority & E-E-A-T (6): author bio, citations, brand mentions, freshness timestamp...
📁 Off-Page & Measurement (6): AI visibility monitoring, prompt testing log, share of voice...
```

---

## Database

### Sheet: `technical_audit`
| Column | Type | Notes |
|---|---|---|
| audit_id | UUID | |
| category | ENUM | crawlability/indexability/performance/mobile/security/schema/architecture |
| item | STRING | Checklist item description |
| severity | ENUM | critical/high/medium/low |
| status | ENUM | not_checked/pass/fail/in_progress/fixed/na |
| url | STRING | URL liên quan |
| details | TEXT | Findings |
| recommendation | TEXT | |
| assigned_to | STRING | |
| due_date | DATE | |
| fixed_date | DATE | |
| evidence | STRING | Link screenshot |

### Sheet: `onpage_checklist`
| Column | Type | Notes |
|---|---|---|
| check_id | UUID | |
| url | STRING | |
| silo_id | STRING | FK |
| category | ENUM | |
| item | STRING | |
| status | ENUM | not_checked/pass/fail/na |
| current_value | TEXT | |
| recommended_value | TEXT | |

### Sheet: `geo_checklist`
| Column | Type | Notes |
|---|---|---|
| geo_id | UUID | |
| url | STRING | |
| silo_id | STRING | FK |
| category | ENUM | technical/content_structure/authority/measurement |
| item | STRING | |
| status | ENUM | not_checked/pass/fail/in_progress/na |
| details | TEXT | |

---

## UI Features

**Technical Audit:**
- Progress dashboard: % by category, severity breakdown (stacked bar)
- Filter: by category / severity / status / assignee
- Bulk update: check/uncheck nhiều items
- AI summary panel: auto-generate findings + quick wins
- Re-audit: snapshot + reset

**On-Page:**
- URL selector dropdown (từ silo)
- Per-page score card (%)
- Score comparison: bảng multi-page side-by-side

**GEO:**
- Radar chart 4 dimensions
- AI score panel với improvement list
- Prompt testing log table
- AI visibility tracker

---

## Service & AI API

```javascript
AuditService.loadTemplate(projectId)
AuditService.getAll(projectId)
AuditService.updateItem(projectId, auditId, data)
AuditService.createSnapshot(projectId)

OnPageService.generateForUrl(projectId, url, siloId)
OnPageService.updateItem(projectId, checkId, data)
OnPageService.getScoreByUrl(projectId)

GeoService.generateForUrl(projectId, url, siloId)
GeoService.updateItem(projectId, geoId, data)

AIService.summarizeAuditFindings(failedItems[])
// → {executive_summary, top_fixes[], quick_wins[]}

AIService.scoreGeoContent(contentOrOutline)
// → {total_score, dimensions{}, improvements[], critical_fixes[]}
```
