# Rule: Performance & GAS Constraints

## Nguyên Tắc Vàng

**Batch > Single. Cache > Re-fetch. Client-filter > Server-filter.**

## 1. Batch Read — KHÔNG gọi Sheet nhiều lần

```javascript
// ❌ SAI
function getByCluster(spreadsheetId, cluster) {
  // Nhiều API calls → chậm + tốn quota
}

// ✅ ĐÚNG
function getByCluster(spreadsheetId, cluster) {
  const all = SheetDB.getRows(spreadsheetId, 'keywords'); // 1 sheet read
  return all.filter(k => k.cluster_group === cluster);
}
```

## 2. Batch Write — setValues() thay vì loop setValue()

```javascript
// ❌ SAI — N API calls
rows.forEach((row, i) => {
  sheet.getRange(i + 2, intentColIdx + 1).setValue(row.intent);
});

// ✅ ĐÚNG — 1 API call
const updates = rows.map(row => [row.intent]);
sheet.getRange(2, intentColIdx + 1, rows.length, 1).setValues(updates);
```

## 3. CacheService TTL Rules

```javascript
const CACHE_TTL = {
  rate_card: 3600,  // 1 giờ — data ít thay đổi
  config:    600,   // 10 phút
  silo_tree: 300,   // 5 phút
  keywords:  60     // 1 phút
};
```

## 4. Pagination Client-side

```javascript
// Frontend load toàn bộ data, paginate trong Alpine.js
get paginatedKeywords() {
  const start = (this.page - 1) * 50;
  return this.filteredKeywords.slice(start, start + 50);
}
// KHÔNG load từng page từ server → tránh nhiều GAS calls
```

## 5. Chunked AI Processing

```javascript
// AIService.gs — tránh GAS timeout 6 phút
function classifyIntentBatch(keywords) {
  const CHUNK_SIZE = 50; // Gemini xử lý 50 keywords/prompt
  const chunks = [];
  for (let i = 0; i < keywords.length; i += CHUNK_SIZE) {
    chunks.push(keywords.slice(i, i + CHUNK_SIZE));
  }
  return chunks.flatMap(chunk => callGemini(buildIntentPrompt(chunk)));
}
```

## 6. Trigger Monitoring

```javascript
function dailySync() {
  const start = Date.now();
  Logger.log(`[dailySync] Started at ${new Date().toISOString()}`);
  // ... sync logic ...
  const elapsed = (Date.now() - start) / 1000;
  Logger.log(`[dailySync] Completed in ${elapsed}s`);
  if (elapsed > 300) Logger.log('[dailySync] WARNING: Near 6-min timeout');
}
```

## GAS Quotas Reference

| Limit | Value | Strategy |
|---|---|---|
| Execution time | 6 min/call | Split batch, use triggers |
| UrlFetch calls | 20,000/day | Cache responses |
| Spreadsheet read | 300 req/min | Batch read once |
| Script triggers | 20 total | Minimize triggers |
| Cache size | 100KB/item | Compress large data |
| Properties storage | 500KB total | Only for config/keys |

## Gemini API Limits

- Free tier (gemini-2.0-flash): **15 RPM / 1M tokens/day**
- Batch 50 keywords/prompt — KHÔNG gọi 1 keyword/call
- Cache classify result (TTL 1h)
- KHÔNG gọi Gemini trong scheduled triggers — chỉ khi user trigger

## GSC API Limits

- searchAnalytics: 1,200 req/day/user
- urlInspection: 2,000 req/day/property
- Strategy: sync 3 ngày/lần, inspect top URLs hàng tuần, batch 10 URLs/run

## Performance Targets

| Test | Target |
|---|---|
| Load 500 keywords | < 3s render |
| Load silo tree 100 nodes | < 2s |
| Dashboard aggregate | < 5s |
| Import CSV 100 rows | < 10s |
| AI Classify 100 keywords | < 30s |
| GSC sync 5,000 rows | < 4 min |
