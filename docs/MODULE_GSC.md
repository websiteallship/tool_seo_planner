# MODULE_GSC — Google Search Console Integration (M10)

## Logic & Workflow Nghiệp Vụ

### 1. Setup Kết Nối GSC (1 lần)
```
Planner vào Settings → GSC Integration
  → Nhập gsc_site_url (vd: sc-domain:example.com)
  → Click "Test Connection"
  → GAS OAuth tự xử lý authentication
  → Nếu OK: hiển thị "Connected ✅ | {n} properties found"
  → Save vào _config sheet
```

### 2. Daily Auto-Sync Performance (Trigger)
```
[Hàng ngày 2:00 AM — Time-based trigger]
GSCService.syncPerformance(projectId):
  → Pull data 3 ngày gần nhất từ searchAnalytics.query API
      Dimensions: query, page, country, device
      rowLimit: 25,000 rows/request
  → Upsert vào gsc_performance sheet (by date+query+page key)
  → Enrichment:
      keywords.current_position ← avg position per keyword
      rankings sheet ← daily position snapshot
  → Update gsc_last_sync timestamp trong _projects
```

### 3. Weekly URL Inspection (Trigger)
```
[Mỗi Chủ Nhật 3:00 AM]
GSCService.batchInspectUrls(projectId):
  → Lấy top 100 URLs priority từ silo_structure (pillar trước)
  → Loop inspect từng URL: urlInspection.index:inspect API
  → Lưu kết quả vào gsc_index_status sheet
  → Enrichment:
      silo_structure: update index badge
      technical_audit: auto-fill indexing status items
  Quota: max 2,000 req/ngày — tự throttle nếu gần limit
```

### 4. Manual Sync
```
Planner click "Sync Now" trên GSC Dashboard
  → Chạy ngay GSCService.syncPerformance() (trigger thủ công)
  → Progress spinner + "Đang đồng bộ..."
  → Khi xong: refresh dashboard metrics
```

### 5. URL Inspection Tool (On-demand)
```
Planner nhập URL vào input → "Inspect"
  → GSCClient.inspectUrl(siteUrl, url)
  → Kết quả hiện inline:
      Verdict: PASS ✅ / NEUTRAL ⚠️ / FAIL ❌
      Coverage state, Canonical, Mobile usability
      Last crawl time, Rich results detected
```

### Quy Tắc Nghiệp Vụ
- **Data delay**: GSC data delay 2-3 ngày → sync luôn pull last 3 ngày
- **Retention**: 16 tháng data trong GSC → lưu lại trong Sheets vô thời hạn
- **Enrichment**: GSC data → tự động populate vào modules khác (không cần planner làm thủ công)
- **Error handling**: nếu quota vượt → log warning, tiếp tục lần sau

---

## Database

### Sheet: `gsc_performance`
| Column | Type | Notes |
|---|---|---|
| record_id | UUID | |
| query | STRING | Search query |
| page | STRING | URL |
| clicks | NUMBER | |
| impressions | NUMBER | |
| ctr | NUMBER | % |
| position | NUMBER | Avg position |
| country | STRING | |
| device | ENUM | desktop/mobile/tablet |
| date | DATE | |
| synced_at | DATETIME | |

### Sheet: `gsc_index_status`
| Column | Type | Notes |
|---|---|---|
| inspection_id | UUID | |
| url | STRING | |
| verdict | ENUM | PASS/NEUTRAL/FAIL |
| coverage_state | STRING | Trạng thái index |
| robotstxt_state | ENUM | ALLOWED/DISALLOWED |
| indexing_state | STRING | |
| page_fetch_state | STRING | |
| crawled_as | ENUM | DESKTOP/MOBILE |
| canonical_url | STRING | Google-selected |
| user_canonical | STRING | |
| mobile_usability | STRING | |
| mobile_issues | JSON | |
| last_crawl_time | DATETIME | |
| inspected_at | DATETIME | |

---

## API Endpoints

| Endpoint | Action | Frequency |
|---|---|---|
| `searchAnalytics.query` | Performance data | Daily |
| `urlInspection/index:inspect` | Index status per URL | Weekly |
| `sitemaps.list` | Sitemap status | Weekly |

**OAuth Scopes** (appsscript.json):
```json
"https://www.googleapis.com/auth/webmasters.readonly"
```

---

## UI Features (GSC Dashboard Page)

- **Connection status** badge: ✅/❌ + property name
- **Sync status**: Last sync time, Next scheduled sync, Manual "Sync Now"
- **Performance overview** (last 28 days): Clicks, Impressions, CTR, Avg Position KPIs
- **Trend chart**: Line chart clicks + impressions over time
- **Top queries table**: Sort by clicks/impressions/CTR/position
- **Top pages table**: Aggregated by URL
- **URL Inspection tool**: Input → Inspect → inline result
- **Index coverage summary**: Indexed / Excluded / Error counts
- **Sitemap status**: List sitemaps + badges
- **Device breakdown**: Desktop vs Mobile bar chart

---

## Service API

```javascript
GSCService.syncPerformance(projectId)
GSCService.batchInspectUrls(projectId, urls[])
GSCService.getSitemapStatus(projectId)
GSCService.getPerformanceSummary(projectId, days)
// → {clicks, impressions, ctr, position, trend[]}

GSCClient.querySearchAnalytics(siteUrl, {startDate, endDate, dimensions, rowLimit})
GSCClient.inspectUrl(siteUrl, inspectionUrl)
GSCClient.listSitemaps(siteUrl)
```
