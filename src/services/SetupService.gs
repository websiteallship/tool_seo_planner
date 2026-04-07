// services/SetupService.gs — Setup & Config management
// Quản lý Script Properties (API keys, Drive Folder ID), init triggers

const SetupService = {
  /**
   * Lấy current config (không expose sensitive keys)
   */
  getConfig() {
    const props = PropertiesService.getScriptProperties();
    return {
      drive_folder_id: props.getProperty('DRIVE_FOLDER_ID') || '',
      gemini_api_key:  props.getProperty('GEMINI_API_KEY')  || '',
      gsc_configured:  !!props.getProperty('DRIVE_FOLDER_ID'),
    };
  },

  /**
   * Lưu config vào Script Properties
   * @param {Object} data — { drive_folder_id?, gemini_api_key? }
   */
  saveConfig(data) {
    const props = PropertiesService.getScriptProperties();

    if (data.drive_folder_id) {
      const folderId = data.drive_folder_id.trim();
      props.setProperty('DRIVE_FOLDER_ID', folderId);

      // Auto-tạo (hoặc tìm) _ToolSEO_Master spreadsheet trong folder
      const masterSheetId = SetupService._ensureMasterSpreadsheet(folderId);
      props.setProperty('MASTER_SHEET_ID', masterSheetId);
    }

    if (data.gemini_api_key) {
      props.setProperty('GEMINI_API_KEY', data.gemini_api_key.trim());
    }

    Logger.log('[SetupService] INFO: Config saved');
    return SetupService.getConfig();
  },

  /**
   * Tìm hoặc tạo _ToolSEO_Master spreadsheet trong Drive folder
   * @param {string} folderId
   * @returns {string} spreadsheetId của Master
   */
  _ensureMasterSpreadsheet(folderId) {
    try {
      const folder = DriveApp.getFolderById(folderId);

      // Tìm file _ToolSEO_Master đã tồn tại trong folder
      const files = folder.getFilesByName('_ToolSEO_Master');
      if (files.hasNext()) {
        const existing = files.next();
        const masterId = existing.getId();
        // Đảm bảo tab _projects tồn tại
        SetupService._ensureProjectsTab(masterId);
        Logger.log(`[SetupService] INFO: Found existing _ToolSEO_Master: ${masterId}`);
        return masterId;
      }

      // Tạo mới _ToolSEO_Master trong folder
      const ss = SpreadsheetApp.create('_ToolSEO_Master');
      const file = DriveApp.getFileById(ss.getId());
      file.moveTo(folder);

      // Đổi tên Sheet1 mặc định thành _projects + thêm headers
      SetupService._ensureProjectsTab(ss.getId());

      Logger.log(`[SetupService] INFO: Created _ToolSEO_Master in folder: ${ss.getId()}`);
      return ss.getId();

    } catch (e) {
      throw new Error(`Cannot access Drive Folder: ${e.message}`);
    }
  },

  /**
   * Đảm bảo tab _projects tồn tại trong Master spreadsheet
   * @param {string} spreadsheetId
   */
  _ensureProjectsTab(spreadsheetId) {
    const ss = SpreadsheetApp.openById(spreadsheetId);
    let sheet = ss.getSheetByName('_projects');
    if (!sheet) {
      // Đổi tên sheet đầu tiên hoặc tạo mới
      const sheets = ss.getSheets();
      sheet = sheets[0];
      sheet.setName('_projects');
    }
    // Kiểm tra headers
    if (sheet.getLastColumn() === 0) {
      const headers = ['project_id', 'name', 'client_name', 'domain', 'status',
        'gsc_site_url', 'spreadsheet_id', 'notes', 'created_at', 'updated_at'];
      sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
      sheet.setFrozenRows(1);
    }
  },

  /**
   * Init triggers — delegate sang Triggers.gs
   */
  initTriggers() {
    initTriggers();
    return { ok: true, message: '4 triggers initialized' };
  },

  /**
   * Test kết nối tới Drive Folder
   * @param {string} folderId
   */
  testFolderConnection(folderId) {
    if (!folderId) throw new Error('No Folder ID provided');
    try {
      const folder = DriveApp.getFolderById(folderId.trim());
      return { ok: true, name: folder.getName(), url: folder.getUrl() };
    } catch (e) {
      throw new Error(`Cannot access folder: ${e.message}`);
    }
  },

  /**
   * Backward-compat: Test connection bằng spreadsheetId (legacy)
   */
  testMasterSheetConnection(spreadsheetId) {
    if (!spreadsheetId) throw new Error('No ID provided');
    try {
      // Thử mở như folder trước, rồi fallback sang sheet
      try {
        const folder = DriveApp.getFolderById(spreadsheetId.trim());
        return { ok: true, name: folder.getName(), url: folder.getUrl(), type: 'folder' };
      } catch (_) {
        const ss = SpreadsheetApp.openById(spreadsheetId.trim());
        return { ok: true, name: ss.getName(), url: ss.getUrl(), type: 'sheet' };
      }
    } catch (e) {
      throw new Error(`Connection failed: ${e.message}`);
    }
  }
};
