# Rule: Testing Strategy & QA

## Testing Approach (GAS — No Unit Test Framework)

1. **Manual functional testing** theo checklist dưới đây
2. **GAS Logger** để debug, trace errors
3. **Test data project**: "[TEST] Construction Nha Trang" — project giả lập cố định

## Test Data Fixture

```
Project: "[TEST] Construction Nha Trang"
Domain:  test-construction.com
Niche:   Xây dựng, Nội thất
Market:  Nha Trang, Khánh Hòa
Keywords: 50 sample (CSV fixture)
Silo:    3-level (5 pillar / 15 cluster / 30 support)
GSC:     Not connected (test manual input)
```

## Critical Path Test Cases

### M1 — Project Management
- [ ] Tạo project mới → sheet được tạo tự động trên Drive
- [ ] Project switcher → load đúng data của project được chọn
- [ ] Duplicate project → sheet mới, data settings clone
- [ ] Archive project → không hiển thị trong list, sheet vẫn còn

### M2 — Keyword Research
- [ ] Import CSV 50 keywords → đúng số lượng, không mất data
- [ ] AI Classify Intent → preview đúng, apply vào sheet
- [ ] Map keyword → silo URL → FK update đúng
- [ ] Cannibalization: 2 keywords cùng URL + intent → flag đỏ
- [ ] Export CSV → đúng header + data

### M3 — Silo Architecture
- [ ] Add pillar → hiển thị trong tree
- [ ] Add cluster dưới pillar → level=2, parent_id đúng
- [ ] AI Suggest Silo → tree structure import đúng
- [ ] Export JSON → đúng format

### M4 — Content Outline
- [ ] Generate outline → record tạo trong sheet, status=draft
- [ ] Preview outline → heading hierarchy đúng
- [ ] Update status → draft → reviewed → approved
- [ ] Export Markdown + Export to Google Doc

### M5 — Technical Audit
- [ ] Load template → 55 items import vào sheet
- [ ] Update item status → sheet update đúng
- [ ] AI Summarize → summary từ fail items

### M10 — GSC Integration
- [ ] Test Connection → success hoặc error rõ ràng
- [ ] Manual Sync → data ghi vào gsc_performance sheet
- [ ] URL Inspection → kết quả đúng fields
- [ ] keyword.current_position được update sau sync

### M11 — Dashboard
- [ ] KPI cards load đúng từ sheets
- [ ] Charts render không lỗi
- [ ] Refresh sau Sync → values update

### M12 — Reporting
- [ ] Generate Slides → file tạo trong Drive, placeholders replaced
- [ ] AI narrative → summary insert đúng slide

### M13 — Quotation
- [ ] Rate card CRUD → add/edit/delete
- [ ] Auto-calculate từ silo counts
- [ ] Save quote → versioned trong quotation sheet

## Error Scenarios (BẮT BUỘC handle)

| Scenario | Expected Behavior |
|---|---|
| Gemini API 429 | "AI tạm thời không khả dụng. Thử lại sau." |
| Sheet không tìm thấy | Error rõ ràng, không crash app |
| GSC unauthorized | Hướng dẫn re-authorize + link |
| CSV format sai | Preview lỗi + hướng dẫn format đúng |
| GAS timeout (6 phút) | Partial results + log warning |
| Import duplicate keywords | Cảnh báo, cho phép skip hoặc overwrite |

## Performance Targets

| Test | Target |
|---|---|
| Load 500 keywords | < 3s render |
| Load silo tree 100 nodes | < 2s |
| Dashboard aggregate (5 sheets) | < 5s |
| Import CSV 100 rows | < 10s |
| AI Classify 100 keywords | < 30s |
| GSC sync 5,000 rows | < 4 phút |

## Debug Helpers

```javascript
// Test trong GAS IDE → View → Logs
function testKeywordImport() {
  const projectId = 'test-project-id';
  const result = KeywordService.bulkImport(projectId, [
    {keyword: 'xây nhà nha trang', search_volume: 1200, difficulty: 45}
  ]);
  Logger.log(JSON.stringify(result));
}

function testGeminiConnection() {
  const result = callGeminiSafe('Trả về JSON: {"status": "ok"}');
  Logger.log(result ? 'Gemini OK' : 'Gemini FAILED');
}

function testGSCConnection() {
  const result = GSCClient.querySearchAnalytics('sc-domain:example.com', {
    startDate: '2025-01-01', endDate: '2025-01-03',
    dimensions: ['query'], rowLimit: 5
  });
  Logger.log('GSC rows: ' + result.length);
}
```

## Pre-Deploy Checklist

- [ ] Test toàn bộ critical paths trên GAS IDE
- [ ] Kiểm tra quota usage (UrlFetch, Sheets API)
- [ ] Backup Master Sheet
- [ ] Ghi version + changes vào TRACK_CHANGELOG.md
- [ ] Test trên Chrome (+ optional Firefox)
- [ ] Kiểm tra responsive min 1280px
