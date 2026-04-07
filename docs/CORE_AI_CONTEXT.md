# CORE_AI_CONTEXT — Gemini AI Integration

> AI Copilot trong ToolSEO hỗ trợ tốc độ lập kế hoạch. Tất cả output AI đều cần **human review trước khi apply** — không auto-publish.

---

## Model Configuration

| Setting | Value |
|---|---|
| Model | `gemini-2.0-flash` (free tier) |
| Output format | Always JSON (structured) |
| Storage | API key trong `PropertiesService.getScriptProperties()` |
| Key name | `GEMINI_API_KEY` |

---

## GeminiClient.gs — Core Wrapper

```javascript
function callGemini(prompt, schema = null) {
  const apiKey = PropertiesService.getScriptProperties().getProperty('GEMINI_API_KEY');
  const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;
  
  const payload = {
    contents: [{ parts: [{ text: prompt }] }],
    generationConfig: {
      responseMimeType: 'application/json',
      ...(schema && { responseSchema: schema })
    }
  };
  
  const response = UrlFetchApp.fetch(url, {
    method: 'POST',
    contentType: 'application/json',
    payload: JSON.stringify(payload)
  });
  
  const json = JSON.parse(response.getContentText());
  return JSON.parse(json.candidates[0].content.parts[0].text);
}
```

---

## AI Use Cases & Prompt Templates

### 1. Classify Keyword Intent (AIService.gs)

**Input**: Array keywords  
**Output**: `[{keyword, intent, confidence, reasoning}]`

```
Phân loại search intent cho các keywords SEO sau.
Intent options: informational, commercial, transactional, navigational

Keywords: {keywords_json}

Trả về JSON array: [{keyword, intent, confidence (0-1), reasoning}]
```

---

### 2. Suggest Cluster Groups

**Input**: Array keywords + niche/domain  
**Output**: `[{cluster_name, keywords[], rationale}]`

```
Bạn là SEO expert. Gom nhóm các keywords sau thành topic clusters 
phù hợp với website về {niche}.

Keywords: {keywords_json}

Trả về JSON: [{cluster_name, keywords[], rationale}]
Tối đa {max_clusters} clusters, phân nhóm theo intent + topical relevance.
```

---

### 3. Suggest Silo Structure

**Input**: Clusters + domain + target market  
**Output**: Silo hierarchy JSON

```
Thiết kế Silo Architecture cho website {domain} ngành {niche}, 
thị trường {target_market}.

Topic clusters: {clusters_json}

Trả về JSON silo hierarchy:
[{
  type: pillar|cluster|support,
  name: string,
  url_slug: string,
  primary_keyword: string,
  children: [...recursive]
}]
```

---

### 4. Generate Content Outline

**Input**: Page metadata (keyword, intent, silo_type, competitors)  
**Output**: Structured outline JSON

```
Tạo content outline chuẩn SEO, E-E-A-T cho trang:
- Primary keyword: {keyword}
- Search intent: {intent}
- Loại trang: {content_type}
- Word count target: {word_count}
- LSI keywords: {lsi_keywords}
- Competitor URLs: {competitor_urls}

Trả về JSON:
{
  h1: string,
  meta_title: string (≤60 chars),
  meta_description: string (≤155 chars),
  sections: [{level: H2|H3, heading, points[], keywords[]}],
  internal_link_suggestions: string[],
  schema_types: string[],
  eeat_notes: string
}
```

---

### 5. Expand LSI & PAA

**Input**: Primary keyword + existing LSI  
**Output**: `{lsi_keywords[], paa_questions[], semantic_entities[]}`

```
Với primary keyword "{keyword}", hãy:
1. Gợi ý 15-20 LSI keywords và semantic variations
2. Liệt kê 10 câu hỏi People-Also-Ask (PAA) phổ biến nhất
3. Xác định 5-8 semantic entities quan trọng

Trả về JSON: {lsi_keywords[], paa_questions[], semantic_entities[]}
```

---

### 6. GEO Score Content

**Input**: Content text hoặc outline  
**Output**: GEO score per dimension

```
Đánh giá nội dung sau theo tiêu chí GEO (Generative Engine Optimization):

Nội dung: {content_or_outline}

Chấm điểm (0-10) cho từng dimension:
1. Answer-First Format (direct answer trong 100 words đầu)
2. Content Structure (modular, question headings, lists)
3. Authority Signals (author bio, citations, brand mentions)
4. Fact Density (số liệu, data, stats)

Trả về JSON:
{
  total_score: number,
  dimensions: {answer_first, structure, authority, fact_density},
  strengths: string[],
  improvements: string[],
  critical_fixes: string[]
}
```

---

### 7. Summarize Audit Findings

**Input**: Array audit items với status=fail + details  
**Output**: Executive summary + prioritized fixes

```
Tóm tắt kết quả Technical SEO Audit cho website {domain}:

Các vấn đề phát hiện: {failed_items_json}

Trả về JSON:
{
  executive_summary: string (3-5 câu),
  critical_count: number,
  high_count: number,
  top_fixes: [{issue, recommendation, priority, estimated_effort}],
  quick_wins: string[]
}
```

---

## Error Handling

```javascript
function callGeminiSafe(prompt, schema = null) {
  try {
    return callGemini(prompt, schema);
  } catch (e) {
    Logger.log('Gemini error: ' + e.message);
    return null; // Caller handles null gracefully
  }
}
```

## Rate Limiting

- Gemini 2.0 Flash free tier: 15 RPM, 1M tokens/ngày
- Batch keywords trong 1 call thay vì nhiều calls
- Cache kết quả classify intent (CacheService, TTL 1 giờ)
