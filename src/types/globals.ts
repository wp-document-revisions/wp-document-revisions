// WordPress Document Revisions Global Types

export interface WPDocumentRevisionsGlobals {
  nonce: string;
  restoreConfirmation: string;
  lockError: string;
  lostLockNotice: string;
  lostLockNoticeLogo: string;
  lostLockNoticeTitle: string;
  postUploadNotice: string;
  extension: string;
}

export interface WPAPISettings {
  root: string;
  nonce: string;
}

export interface PluploadFile {
  id: string;
  name: string;
  size: number;
  percent: number;
  status: number;
}

export interface PluploadResponse {
  response: string;
}

export interface PluploadUploader {
  bind(
    event: string,
    callback: (uploader: PluploadUploader, file: PluploadFile, response: PluploadResponse) => void
  ): void;
}

export interface WPCookies {
  set(
    name: string,
    value: string,
    expires: number,
    path: boolean | string,
    domain: boolean | string,
    secure: boolean,
    sameSite?: 'strict' | 'lax' | 'none'
  ): void;
}

// Extend the global Window interface
declare global {
  interface Window {
    wp_document_revisions: WPDocumentRevisionsGlobals;
    wpApiSettings: WPAPISettings;
    ajaxurl: string;
    wpCookies: WPCookies;
    autosave: () => void;
    convertEntities?: (text: string) => string;
    uploader?: any;
    dialogArguments?: Window;
    tb_remove?: () => void;
    wp?: {
      serverSideRender?: any;
      [key: string]: any;
    };
  }

  const nonce: string;
  const user: string;
  const processed: string;
}

export {};
