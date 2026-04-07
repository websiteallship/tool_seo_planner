// SheetDB.gs — Generic CRUD layer cho Google Sheets
// KHÔNG chứa business logic — chỉ read/write dữ liệu

const SheetDB = {
  /**
   * Lấy tất cả rows từ sheet, map thành array of objects
   * @param {string} spreadsheetId
   * @param {string} sheetName
   * @returns {Object[]}
   */
  getRows(spreadsheetId, sheetName) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    if (!sheet) throw new Error(`Sheet '${sheetName}' not found`);
    const data = sheet.getDataRange().getValues();
    if (data.length < 2) return [];
    const [headers, ...rows] = data;
    // Lọc bỏ rows trống (row đầu tiên rỗng = đã xoá)
    return rows.filter(r => r[0] !== '' && r[0] !== null).map(row =>
      Object.fromEntries(headers.map((h, i) => [h, row[i]]))
    );
  },

  /**
   * Thêm 1 row mới vào cuối sheet
   * @param {string} spreadsheetId
   * @param {string} sheetName
   * @param {Object} data — key phải khớp với header
   */
  addRow(spreadsheetId, sheetName, data) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    if (!sheet) throw new Error(`Sheet '${sheetName}' not found`);
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    sheet.appendRow(headers.map(h => data[h] ?? ''));
  },

  /**
   * Thêm nhiều rows cùng lúc (batch) — tối ưu quota
   * @param {string} spreadsheetId
   * @param {string} sheetName
   * @param {Object[]} dataArray
   */
  batchAddRows(spreadsheetId, sheetName, dataArray) {
    if (!dataArray || !dataArray.length) return;
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    if (!sheet) throw new Error(`Sheet '${sheetName}' not found`);
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    const rows = dataArray.map(data => headers.map(h => data[h] ?? ''));
    sheet.getRange(sheet.getLastRow() + 1, 1, rows.length, headers.length).setValues(rows);
  },

  /**
   * Update 1 row theo primary key
   * @param {string} spreadsheetId
   * @param {string} sheetName
   * @param {string} pkColumn — tên cột primary key
   * @param {string} pkValue — giá trị PK cần tìm
   * @param {Object} updates — chỉ các field cần cập nhật
   * @returns {boolean} — true nếu tìm thấy và update, false nếu không
   */
  updateRow(spreadsheetId, sheetName, pkColumn, pkValue, updates) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    if (!sheet) throw new Error(`Sheet '${sheetName}' not found`);
    const [headers, ...rows] = sheet.getDataRange().getValues();
    const pkIdx = headers.indexOf(pkColumn);
    if (pkIdx === -1) throw new Error(`Column '${pkColumn}' not found`);
    const rowIdx = rows.findIndex(r => String(r[pkIdx]) === String(pkValue));
    if (rowIdx === -1) return false;
    const updatedRow = headers.map((h, i) => h in updates ? updates[h] : rows[rowIdx][i]);
    sheet.getRange(rowIdx + 2, 1, 1, headers.length).setValues([updatedRow]);
    return true;
  },

  /**
   * Xoá 1 row theo primary key
   * @param {string} spreadsheetId
   * @param {string} sheetName
   * @param {string} pkColumn
   * @param {string} pkValue
   * @returns {boolean}
   */
  deleteRow(spreadsheetId, sheetName, pkColumn, pkValue) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    if (!sheet) throw new Error(`Sheet '${sheetName}' not found`);
    const [headers, ...rows] = sheet.getDataRange().getValues();
    const pkIdx = headers.indexOf(pkColumn);
    if (pkIdx === -1) throw new Error(`Column '${pkColumn}' not found`);
    const rowIdx = rows.findIndex(r => String(r[pkIdx]) === String(pkValue));
    if (rowIdx === -1) return false;
    sheet.deleteRow(rowIdx + 2);
    return true;
  },

  /**
   * Tìm 1 row theo điều kiện (field = value)
   * @param {string} spreadsheetId
   * @param {string} sheetName
   * @param {string} field
   * @param {*} value
   * @returns {Object|null}
   */
  findOne(spreadsheetId, sheetName, field, value) {
    const rows = SheetDB.getRows(spreadsheetId, sheetName);
    return rows.find(r => String(r[field]) === String(value)) || null;
  }
};
