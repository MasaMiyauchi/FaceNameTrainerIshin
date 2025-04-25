/**
 * ローカルストレージを利用した進捗・結果保存モジュール
 * 学習の記録、統計、苦手な顔の追跡などを管理する
 */

// ローカルストレージのキープレフィックス（アプリケーション固有のキーを作成するため）
const STORAGE_PREFIX = 'face_name_trainer_';

/**
 * 保存するデータの種類を定義
 */
const STORAGE_KEYS = {
    // ユーザーの設定
    SETTINGS: STORAGE_PREFIX + 'settings',
    // 学習セッションの結果
    RESULTS: STORAGE_PREFIX + 'results',
    // 苦手な顔のデータ
    WEAK_FACES: STORAGE_PREFIX + 'weak_faces',
    // 最後に学習したセッションのデータ
    LAST_SESSION: STORAGE_PREFIX + 'last_session',
    // 進捗データ
    PROGRESS: STORAGE_PREFIX + 'progress'
};

/**
 * ローカルストレージにデータを保存する
 * 
 * @param {string} key - 保存するデータのキー
 * @param {*} data - 保存するデータ（JSON.stringify可能なもの）
 * @returns {boolean} - 保存の成功/失敗
 */
function saveToStorage(key, data) {
    try {
        // データをJSON文字列に変換
        const serializedData = JSON.stringify(data);
        // ローカルストレージに保存
        localStorage.setItem(key, serializedData);
        return true;
    } catch (error) {
        console.error('保存エラー:', error);
        return false;
    }
}

/**
 * ローカルストレージからデータを読み込む
 * 
 * @param {string} key - 読み込むデータのキー
 * @param {*} defaultValue - データが存在しない場合のデフォルト値
 * @returns {*} - 読み込んだデータまたはデフォルト値
 */
function loadFromStorage(key, defaultValue = null) {
    try {
        // ローカルストレージからデータを取得
        const serializedData = localStorage.getItem(key);
        // データが存在しない場合はデフォルト値を返す
        if (serializedData === null) {
            return defaultValue;
        }
        // JSON文字列をオブジェクトに変換して返す
        return JSON.parse(serializedData);
    } catch (error) {
        console.error('読み込みエラー:', error);
        return defaultValue;
    }
}

/**
 * 指定したキーのデータをローカルストレージから削除する
 * 
 * @param {string} key - 削除するデータのキー
 * @returns {boolean} - 削除の成功/失敗
 */
function removeFromStorage(key) {
    try {
        localStorage.removeItem(key);
        return true;
    } catch (error) {
        console.error('削除エラー:', error);
        return false;
    }
}

/**
 * ユーザー設定を保存する
 * 
 * @param {Object} settings - 保存する設定オブジェクト
 * @returns {boolean} - 保存の成功/失敗
 */
function saveUserSettings(settings) {
    return saveToStorage(STORAGE_KEYS.SETTINGS, settings);
}

/**
 * ユーザー設定を読み込む
 * 
 * @returns {Object} - 読み込んだ設定オブジェクトまたはデフォルト設定
 */
function loadUserSettings() {
    // デフォルト設定
    const defaultSettings = {
        difficulty: 'easy',    // 難易度（'easy', 'medium', 'hard'）
        region: 'japan',       // 地域（'japan', 'usa', 'europe', 'asia', 'mixed'）
        timerDuration: 60,     // 学習タイマーの秒数
        showHints: true,       // ヒントを表示するかどうか
        theme: 'light'         // テーマ（'light', 'dark'）
    };
    
    return loadFromStorage(STORAGE_KEYS.SETTINGS, defaultSettings);
}

/**
 * 学習セッションの結果を保存する
 * 
 * @param {Object} result - セッション結果オブジェクト
 * @returns {boolean} - 保存の成功/失敗
 */
function saveSessionResult(result) {
    // 既存の結果データを読み込む
    const results = loadFromStorage(STORAGE_KEYS.RESULTS, []);
    
    // 新しい結果を追加
    results.push({
        ...result,
        timestamp: new Date().toISOString() // タイムスタンプを追加
    });
    
    // 最大100件を保持する（古いものから削除）
    if (results.length > 100) {
        results.shift();
    }
    
    // 結果リストを保存
    return saveToStorage(STORAGE_KEYS.RESULTS, results);
}

/**
 * 学習セッションの結果履歴を読み込む
 * 
 * @param {number} limit - 取得する結果の最大数
 * @returns {Array} - 結果オブジェクトの配列
 */
function loadSessionResults(limit = 100) {
    const results = loadFromStorage(STORAGE_KEYS.RESULTS, []);
    
    // 最新のものから指定件数を返す
    return results.slice(-limit).reverse();
}

/**
 * 苦手な顔データを更新する
 * 
 * @param {Object} faceData - 顔データオブジェクト
 * @param {boolean} isCorrect - 回答が正解だったかどうか
 * @returns {boolean} - 保存の成功/失敗
 */
function updateWeakFaces(faceData, isCorrect) {
    // 苦手な顔のデータを読み込む
    const weakFaces = loadFromStorage(STORAGE_KEYS.WEAK_FACES, {});
    
    // 顔データのIDを生成（実際のアプリでは一意のIDが必要）
    const faceId = faceData.id || faceData.name;
    
    // この顔のデータが存在しない場合は初期化
    if (!weakFaces[faceId]) {
        weakFaces[faceId] = {
            face: faceData,
            attempts: 0,
            correct: 0,
            lastSeen: null
        };
    }
    
    // データを更新
    weakFaces[faceId].attempts += 1;
    if (isCorrect) {
        weakFaces[faceId].correct += 1;
    }
    weakFaces[faceId].lastSeen = new Date().toISOString();
    
    // 苦手度（正解率の逆）を計算
    weakFaces[faceId].weaknessScore = 1 - (weakFaces[faceId].correct / weakFaces[faceId].attempts);
    
    // 更新したデータを保存
    return saveToStorage(STORAGE_KEYS.WEAK_FACES, weakFaces);
}

/**
 * 最も苦手な顔のリストを取得する
 * 
 * @param {number} limit - 取得する顔の最大数
 * @returns {Array} - 苦手な顔のデータ配列（苦手度順）
 */
function getWeakestFaces(limit = 10) {
    const weakFaces = loadFromStorage(STORAGE_KEYS.WEAK_FACES, {});
    
    // オブジェクトを配列に変換
    const facesArray = Object.values(weakFaces);
    
    // 試行回数が2回以上のものだけを対象にし、苦手度でソート
    return facesArray
        .filter(face => face.attempts >= 2)
        .sort((a, b) => b.weaknessScore - a.weaknessScore)
        .slice(0, limit);
}

// グローバルに公開するオブジェクト
const StorageModule = {
    saveUserSettings,
    loadUserSettings,
    saveSessionResult,
    loadSessionResults,
    updateWeakFaces,
    getWeakestFaces,
    // 低レベルAPI
    saveToStorage,
    loadFromStorage,
    removeFromStorage,
    // 定数
    KEYS: STORAGE_KEYS
};