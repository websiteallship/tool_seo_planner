# Rule: Security & Data Integrity

## Secrets Management

**KHÔNG BAO GIỜ** lưu credentials trong code source.

```javascript
// ✅ ĐÚNG — PropertiesService
const key = PropertiesService.getScriptProperties().getProperty('GEMINI_API_KEY');

// ❌ SAI — hardcode
const key = 'AIzaSy...'; // KHÔNG được
```

## Script Properties (Toàn bộ secrets)

| Key | Notes |
|---|---|
| `GEMINI_API_KEY` | Gemini API key từ aistudio.google.com |
| `MASTER_SHEET_ID` | Master spreadsheet ID |
| `REPORT_TEMPLATE_SLIDES_ID` | Google Slides template |
| `REPORT_TEMPLATE_DOCS_ID` | Google Docs template |
| `PROJECT_TEMPLATE_ID` | Project sheet template |

Tổng dung lượng Properties: **≤ 500KB** — chỉ lưu config nhỏ, không lưu data.

## GAS Web App Security

- Execute as: **USER_DEPLOYING** (chạy với quyền của owner)
- Access: **ANYONE** (không cần Google account — internal tool)
- GSC OAuth: managed tự động bởi GAS, scope `webmasters.readonly`

## AI Data Protection

- AI KHÔNG tự ghi thẳng vào database (xem rule 09_ai_integration.md)
- AI output luôn qua preview → user confirm → save
- Gemini API key KHÔNG expose ra client-side HTML

## Client-side Security

- Mọi `google.script.run` PHẢI có `.withFailureHandler()`
- KHÔNG expose spreadsheet IDs trong HTML/JS client
- Validate input phía Service.gs trước khi ghi Sheet

## Data Integrity

```javascript
// Validation pattern trong Service.gs
function _validateKeyword(data) {
  if (!data.keyword || !data.keyword.trim()) throw new Error('keyword is required');
  if (data.keyword.length > 500) throw new Error('keyword quá dài (max 500 chars)');
  if (data.search_volume && isNaN(data.search_volume)) throw new Error('search_volume phải là số');
}
```

## Backup Strategy

- Backup Master Sheet trước mỗi deploy
- Per-project Sheets tự động versioned qua Google Drive history
- Không xóa archived projects — chỉ set status='archived'

## Concurrent Write Safety

```javascript
// Khi trigger và manual sync chạy cùng lúc
function syncWithLock(projectId) {
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(5000)) return { error: 'Another sync is running' };
  try { return GSCService.syncPerformance(projectId); }
  finally { lock.releaseLock(); }
}
```
