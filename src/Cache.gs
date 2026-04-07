// Cache.gs — Cache helper dùng CacheService
// Tránh gọi Sheet/API nhiều lần trong 1 request cycle

const CACHE_TTL = {
  rate_card:        3600, // 1 giờ
  config:            600, // 10 phút
  silo_tree:         300, // 5 phút
  keywords:           60, // 1 phút
  cannibalization:   120, // 2 phút — computed từ keywords, không thay đổi nhiều
  keyword_stats:     120, // 2 phút — computed từ keywords
  gsc_properties:   3600,
};

/**
 * Lấy dữ liệu từ cache, nếu miss thì gọi fetchFn và cache lại
 * @param {string} key — cache key duy nhất
 * @param {Function} fetchFn — function lấy data nếu cache miss
 * @param {number} ttl — thời gian sống (giây)
 * @returns {*}
 */
function getWithCache(key, fetchFn, ttl = 300) {
  const cache = CacheService.getScriptCache();
  const hit = cache.get(key);
  if (hit) return JSON.parse(hit);
  const data = fetchFn();
  if (data !== null && data !== undefined) {
    // Giới hạn 100KB/item — compress nếu cần
    const serialized = JSON.stringify(data);
    if (serialized.length < 100000) {
      cache.put(key, serialized, ttl);
    }
  }
  return data;
}

/**
 * Xoá cache theo key hoặc pattern prefix
 * @param {string[]} keys
 */
function invalidateCache(keys) {
  const cache = CacheService.getScriptCache();
  cache.removeAll(keys);
}

/**
 * Build cache key chuẩn để tránh collision
 * @param {string} module
 * @param {string} projectId
 * @param {string} [suffix]
 * @returns {string}
 */
function cacheKey(module, projectId, suffix = '') {
  return `${module}:${projectId}${suffix ? ':' + suffix : ''}`;
}
