# OPS_TESTING — Testing Strategy & QA Checklist

## Testing Approach

ToolSEO (GAS) không có unit test framework — thay vào đó dùng:
1. **Manual functional testing** theo checklist
2. **GAS Logger** để debug và trace errors
3. **Test data project** — project giả lập để test toàn bộ luồng

---

## Test Data Project (Fixture)

Tạo 1 project test sẵn: **"[TEST] Construction Nha Trang"**
- Domain: `test-construction.com`
- Niche: `Xây dựng, Nội thất`
- Target Market: `Nha Trang, Khánh Hòa`
- Keywords: 50 sample keywords (CSV fixture)
- Silo: 3-level sample hierarchy (5 pillar / 15 cluster / 30 support)
- GSC: không kết nối (test manual input)

---

## Core Test Cases (Critical Path)

### M1 — Project Management
- [ ] Tạo project mới → sheet được tạo tự động trên Drive
- [ ] Project switcher → load đúng data của project được chọn
- [ ] Duplicate project → sheet mới được tạo, data settings được clone
- [ ] Archive project → không hiển thị trong list, sheet vẫn còn

### M2 — Keyword Research
- [ ] Import CSV 50 keywords → kết quả đúng số lượng, không mất data
- [ ] AI Classify Intent → preview hiển thị đúng, apply vào sheet
- [ ] Map keyword → silo URL → FK update đúng
- [ ] Cannibalization: thêm 2 keywords cùng URL + intent → flag đỏ hiện ra
- [ ] Export CSV → file download đúng header + data

### M3 — Silo Architecture
- [ ] Add pillar → hiển thị trong tree
- [ ] Add cluster dưới pillar → level=2, parent_id đúng
- [ ] Drag-drop node → parent_id và level update đúng
- [ ] AI Suggest Silo → tree structure được import đúng
- [ ] Export JSON → file đúng format

### M4 — Content Outline
- [ ] Generate single outline → record tạo trong sheet, status=draft
- [ ] Preview outline → heading hierarchy hiển thị đúng
- [ ] Update status → draft → reviewed → approved
- [ ] Export Markdown → file format đúng
- [ ] Export to Google Doc → file được tạo trong Drive

### M5 — Technical Audit
- [ ] Load template → 55 items import vào sheet
- [ ] Update item status → sheet update đúng
- [ ] AI Summarize → summary hiển thị findings từ fail items
- [ ] Re-audit → snapshot tạo, status reset

### M10 — GSC Integration
- [ ] Test Connection → trả về success hoặc error rõ ràng
- [ ] Manual Sync → data ghi vào gsc_performance sheet
- [ ] URL Inspection → kết quả hiển thị inline đúng fields
- [ ] Enrichment: keyword.current_position được update sau sync

### M11 — Dashboard
- [ ] KPI cards load đúng values từ sheets
- [ ] Charts render không lỗi
- [ ] Refresh sau Sync GSC → values update

### M12 — Reporting
- [ ] Generate Slides → file tạo trong Drive với placeholders replaced
- [ ] AI narrative → summary được insert đúng slide
- [ ] History list → file URLs hiển thị

### M13 — Quotation
- [ ] Rate card CRUD → thêm/sửa/xóa items
- [ ] Auto-calculate từ silo counts → totals đúng
- [ ] Save quote → versioned trong quotation sheet
- [ ] Export Sheet → Google Sheet được tạo

---

## Error Scenarios

| Scenario | Expected Behavior |
|---|---|
| Gemini API limit reached (429) | Hiển thị: "AI tạm thời không khả dụng. Thử lại sau." |
| Sheet không tìm thấy | Error rõ ràng, không crash toàn app |
| GSC unauthorized | Hướng dẫn re-authorize với link |
| CSV format sai | Preview lỗi + hướng dẫn format đúng |
| GAS timeout (6 phút) | Partial results + log warning |
| Import duplicate keywords | Cảnh báo, cho phép skip hoặc overwrite |

---

## Performance Testing

| Test | Target |
|---|---|
| Load 500 keywords | < 3s render time |
| Load silo tree 100 nodes | < 2s |
| Dashboard aggregate (5 sheets) | < 5s |
| Import CSV 100 rows | < 10s |
| AI Classify 100 keywords | < 30s (2 chunks × Gemini call) |
| GSC sync 5,000 rows | < 4 phút |

---

## Debug Helpers

```javascript
// Trong GAS IDE — View → Logs
function testKeywordImport() {
  const projectId = 'test-project-id';
  const sampleRows = [
    {keyword: 'xây nhà nha trang', search_volume: 1200, difficulty: 45},
    {keyword: 'thi công nội thất', search_volume: 800, difficulty: 38}
  ];
  const result = KeywordService.bulkImport(projectId, sampleRows);
  Logger.log(JSON.stringify(result));
}

function testGeminiConnection() {
  const result = callGeminiSafe('Trả về JSON: {"status": "ok"}');
  Logger.log(result ? 'Gemini OK' : 'Gemini FAILED');
}

function testGSCConnection() {
  const siteUrl = 'sc-domain:example.com';
  const result = GSCClient.querySearchAnalytics(siteUrl, {
    startDate: '2025-01-01',
    endDate: '2025-01-03',
    dimensions: ['query'],
    rowLimit: 5
  });
  Logger.log('GSC rows: ' + result.length);
}
```
