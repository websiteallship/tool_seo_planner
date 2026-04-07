// GeminiClient.gs — Wrapper gọi Gemini AI API
// Model: gemini-2.0-flash · Auth: API Key từ PropertiesService

/**
 * Gọi Gemini API với prompt và optional response schema
 * @param {string} prompt
 * @param {Object|null} schema — JSON Schema để ép response format
 * @returns {Object} — parsed JSON response
 */
function callGemini(prompt, schema = null) {
  const apiKey = PropertiesService.getScriptProperties().getProperty('GEMINI_API_KEY');
  if (!apiKey) throw new Error('GEMINI_API_KEY not configured in Script Properties');

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
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  });

  if (response.getResponseCode() !== 200) {
    throw new Error(`Gemini API error ${response.getResponseCode()}: ${response.getContentText()}`);
  }

  const json = JSON.parse(response.getContentText());
  return JSON.parse(json.candidates[0].content.parts[0].text);
}

/**
 * Wrapper an toàn — bắt lỗi, log, return null thay vì throw
 * @param {string} prompt
 * @param {Object|null} schema
 * @returns {Object|null}
 */
function callGeminiSafe(prompt, schema = null) {
  try {
    return callGemini(prompt, schema);
  } catch (e) {
    Logger.log('[GeminiClient] ERROR: ' + e.message);
    return null;
  }
}
