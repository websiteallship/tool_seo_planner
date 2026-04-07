// Utils.gs — Utility functions dùng chung toàn hệ thống

/**
 * Tạo UUID v4
 */
function uuid() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = Math.random() * 16 | 0;
    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
  });
}

/**
 * Trả về timestamp ISO hiện tại (Asia/Ho_Chi_Minh)
 */
function nowISO() {
  return new Date().toISOString();
}

/**
 * Format date thành YYYY-MM-DD
 * @param {Date} date
 */
function formatDate(date) {
  return Utilities.formatDate(date || new Date(), 'Asia/Ho_Chi_Minh', 'yyyy-MM-dd');
}

/**
 * Validate object — throw nếu thiếu field bắt buộc
 * @param {Object} data
 * @param {string[]} required — danh sách field bắt buộc
 */
function validateRequired(data, required) {
  required.forEach(field => {
    if (!data[field] && data[field] !== 0) {
      throw new Error(`${field} is required`);
    }
  });
}

/**
 * Slugify string (dùng cho URL, sheet name)
 */
function slugify(str) {
  return str.toLowerCase().trim()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '');
}
