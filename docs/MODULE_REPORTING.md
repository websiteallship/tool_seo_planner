# MODULE_REPORTING — Client Reporting & Quotation (M12+M13)

## Logic & Workflow Nghiệp Vụ

---

### Client Reporting System (M12)

#### Luồng Generate Monthly Report
```
Cuối tháng: Planner click "Generate Monthly Report"
  → Chọn: period (tháng/năm), template (Slides/Docs)
  → ReportService.generateMonthlyReport(projectId, month):
      1. Pull dữ liệu từ các sheets:
           gsc_performance → clicks, impressions, ctr, position (so sánh month vs prev month)
           rankings → top keyword movements (gainers/losers)
           silo_structure → published pages progress
           technical_audit → fixed items vs open items
           backlinks → new live links
           content_outlines → outlines sent to writer
      2. AI: generate executive_summary (3-5 câu, insight-driven)
      3. Copy template Slides từ Drive (templateId từ _config)
      4. Merge data vào placeholders: {{client_name}}, {{total_clicks}}, ...
      5. Nhúng chart images (capture từ Sheets charts)
      6. Save file mới vào client folder trên Drive
      7. Return file URL
  → Planner click link → review → share link với KH
```

#### Report Templates (lưu trên Google Drive)

**Monthly SEO Report (Google Slides):**
- Slide 1: Cover — `{{client_name}}` | SEO Monthly Report | `{{report_month}}`
- Slide 2: Executive Summary — AI-generated narrative + Key wins / Challenges
- Slide 3: Traffic & Visibility — GSC chart + KPI numbers
- Slide 4: Keyword Rankings — Top movers table
- Slide 5: Silo Progress — `{{published_pages}}`/`{{total_pages}}`
- Slide 6: Work Completed — bullet list
- Slide 7: Next 30 Days Plan

**Project Status Update (Google Docs) — 2 tuần/lần:**
- Tóm tắt tiến độ Silo, Outline, Audit fixes list

#### Quy Tắc
- Template phải được setup sẵn trên Drive (Planner tạo 1 lần)
- templateId lưu trong `_config` sheet: `report_template_slides_id`, `report_template_docs_id`
- Báo cáo tạo ra không thay thế — mỗi lần tạo 1 file mới (có timestamp)

---

### Quotation & Pricing Engine (M13)

#### Luồng Tạo Báo Giá
```
Planner vào Quotation module
  → QuotationService.calculate(projectId):
      1. Đọc silo_structure: đếm pages by type (pillar, cluster, support, blog...)
      2. Đọc rate_card: đơn giá từng page type
      3. Calculate:
           pillar_pages × unit_price_pillar
           cluster_pages × unit_price_cluster
           + other line items (audit, reporting, monthly retainer...)
      4. Tổng hợp breakdown {item, quantity, unit_price, total}
      5. Grand total
  → Render bảng báo giá trực quan
  → Planner điều chỉnh số lượng / thêm bớt line items
  → Click "Save Quote" → lưu vào quotation sheet (version++)
  → Click "Export" → Google Sheet hoặc PDF via DriveApp
```

#### Smart Pricing Logic
```
Ví dụ Rate Card:
  Pillar Page Outline:    800,000 VND/page
  Cluster Page Outline:   500,000 VND/page
  Support Page Outline:   300,000 VND/page
  Technical Audit:        2,000,000 VND (fixed)
  Monthly Reporting:      1,500,000 VND/month

Silo structure:
  5 pillar pages × 800K = 4,000,000
  20 cluster × 500K    = 10,000,000
  40 support × 300K    = 12,000,000
  Technical Audit      = 2,000,000
  Reporting × 3 months = 4,500,000
  ─────────────────────────────────
  TOTAL                = 32,500,000 VND
```

---

## Database

### Sheet: `rate_card`
| Column | Type | Notes |
|---|---|---|
| item_id | UUID | |
| category | STRING | Hạng mục (Planning/Reporting/Audit) |
| item_name | STRING | |
| unit | STRING | page/keyword/month/fixed |
| unit_price | NUMBER | |
| currency | ENUM | VND/USD |
| notes | TEXT | |

### Sheet: `quotation`
| Column | Type | Notes |
|---|---|---|
| quote_id | UUID | |
| created_date | DATE | |
| version | NUMBER | Auto-increment per project |
| status | ENUM | draft/sent/approved/rejected |
| total_amount | NUMBER | |
| currency | ENUM | VND/USD |
| breakdown | JSON | [{item_name, quantity, unit_price, total}] |
| notes | TEXT | |

---

## UI Features

**Reporting:**
- Template configuration (Slides/Docs template ID)
- Period selector: tháng, quý
- "Generate Report" button với progress
- Report history: list file links đã tạo
- Preview: iframe embed nếu Google Slides

**Quotation:**
- Rate card management: thêm/sửa/xóa dòng giá
- Auto-calculated quote table từ silo data
- Editable line items (adjust quantity, add custom items)
- Version history: xem các báo giá cũ
- Export: Google Sheet / Print PDF

---

## Service & AI API

```javascript
ReportService.generateMonthlyReport(projectId, { month, year })
ReportService.generateStatusUpdate(projectId)
ReportService.getHistory(projectId)
// → [{reportId, reportType, period, driveUrl, generatedDate, status}]
// Reads from _reports sheet

QuotationService.calculate(projectId)
// → {breakdown[], total, currency}

QuotationService.save(projectId, quoteData)
QuotationService.exportToSheet(projectId, quoteId)
QuotationService.getRateCard(projectId)
QuotationService.updateRateCard(projectId, items[])

AIService.generateReportNarrative(reportData)
// → {executive_summary, highlights[], challenges[], recommendations[]}
```
