/**
 * AI生成顔画像取得のためのAPIラッパー
 * This Person Does Not Exist APIなどを使用して、AIで生成された顔画像を取得するモジュール
 */

/**
 * 顔画像取得に関する定数
 */
const FACE_API_CONSTANTS = {
    // 顔画像を取得するためのエンドポイント
    ENDPOINTS: {
        // This Person Does Not Exist APIのエンドポイント（キャッシュ回避のためタイムスタンプを使用）
        GENERAL: 'https://thispersondoesnotexist.com',
        // スタイル別エンドポイント（将来的な拡張用）
        ASIAN: 'https://this-person-does-not-exist.com/en?new=asian',
        AMERICAN: 'https://this-person-does-not-exist.com/en?new=american',
        EUROPEAN: 'https://this-person-does-not-exist.com/en?new=european'
    },
    // 画像のデフォルトサイズ
    DEFAULT_SIZE: {
        WIDTH: 256,
        HEIGHT: 256
    },
    // 性別（性別指定できるAPIの場合）
    GENDER: {
        ANY: 'any',
        MALE: 'male',
        FEMALE: 'female'
    },
    // 画像形式
    FORMAT: {
        JPEG: 'jpeg',
        PNG: 'png'
    }
};

/**
 * 各地域ごとの顔画像取得パラメータの設定
 */
const REGION_FACE_PARAMS = {
    japan: {
        endpoint: FACE_API_CONSTANTS.ENDPOINTS.ASIAN,
        params: { region: 'japan' }
    },
    usa: {
        endpoint: FACE_API_CONSTANTS.ENDPOINTS.AMERICAN,
        params: { region: 'usa' }
    },
    europe: {
        endpoint: FACE_API_CONSTANTS.ENDPOINTS.EUROPEAN,
        params: { region: 'europe' }
    },
    asia: {
        endpoint: FACE_API_CONSTANTS.ENDPOINTS.ASIAN,
        params: { region: 'asia', exclude: 'japan' }
    },
    mixed: {
        endpoint: FACE_API_CONSTANTS.ENDPOINTS.GENERAL,
        params: {}
    }
};

/**
 * 指定された地域に基づいてAI生成顔画像のURLを取得する
 * 
 * @param {string} region - 取得する顔画像の地域 ('japan', 'usa', 'europe', 'asia', 'mixed')
 * @param {Object} options - 追加オプション（性別、サイズなど）
 * @returns {string} - 顔画像のURL
 */
function getFaceImageUrl(region, options = {}) {
    // デフォルト値を設定
    const config = {
        gender: options.gender || FACE_API_CONSTANTS.GENDER.ANY,
        width: options.width || FACE_API_CONSTANTS.DEFAULT_SIZE.WIDTH,
        height: options.height || FACE_API_CONSTANTS.DEFAULT_SIZE.HEIGHT,
        format: options.format || FACE_API_CONSTANTS.FORMAT.JPEG
    };
    
    // 地域が指定されていない場合はmixed（混合）を使用
    const targetRegion = region && REGION_FACE_PARAMS[region] ? region : 'mixed';
    const regionParams = REGION_FACE_PARAMS[targetRegion];
    
    // 実際のAPI利用ではないため、キャッシュ回避のためのタイムスタンプを付与
    // 実際のプロジェクトでは、This Person Does Not Exist APIなどの実際のAPIを使用すること
    const timestamp = new Date().getTime();
    
    // 実際のAPIではなくダミーURLを返す（デモ用）
    // 本番環境では実際のAPIエンドポイントを使用すること
    return `https://thispersondoesnotexist.com?region=${targetRegion}&timestamp=${timestamp}`;
}

/**
 * 複数の顔画像URLをまとめて取得する
 * 
 * @param {string} region - 取得する顔画像の地域 ('japan', 'usa', 'europe', 'asia', 'mixed')
 * @param {number} count - 取得する画像の数
 * @param {Object} options - 追加オプション（性別、サイズなど）
 * @returns {Array} - 顔画像URLの配列
 */
function getMultipleFaceImageUrls(region, count, options = {}) {
    const urls = [];
    
    // 指定された数だけURLを生成
    for (let i = 0; i < count; i++) {
        // 性別をバランスよく設定（男女交互にするなど）
        const genderOption = options.gender || 
            (i % 2 === 0 ? FACE_API_CONSTANTS.GENDER.MALE : FACE_API_CONSTANTS.GENDER.FEMALE);
        
        // 各画像のオプションをコピーし、適切な性別を設定
        const imageOptions = Object.assign({}, options, { gender: genderOption });
        
        // URLを取得して配列に追加
        urls.push(getFaceImageUrl(region, imageOptions));
    }
    
    return urls;
}

/**
 * 指定されたURLの顔画像をプリロードする（パフォーマンス向上のため）
 * 
 * @param {Array} urls - プリロードする画像のURL配列
 * @returns {Promise} - すべての画像のプリロードが完了したときに解決されるPromise
 */
function preloadImages(urls) {
    // 各URLに対してプリロード処理を行うPromiseの配列を作成
    const promises = urls.map(url => {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(url);
            img.onerror = () => reject(new Error(`Failed to load image: ${url}`));
            img.src = url;
        });
    });
    
    // すべてのPromiseが完了したときに解決されるPromiseを返す
    return Promise.all(promises);
}

// グローバルに公開するオブジェクト
const FaceApiModule = {
    getFaceImageUrl,
    getMultipleFaceImageUrls,
    preloadImages,
    CONSTANTS: FACE_API_CONSTANTS
};