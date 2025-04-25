/**
 * 顔画像取得用APIモジュール
 * このモジュールはAI生成顔画像を取得するためのインターフェースを提供します
 */

const FaceAPI = (function() {
    // 各地域に適した顔画像を取得するためのベースURL
    const API_ENDPOINTS = {
        japan: 'https://thispersondoesnotexist.com/', // 実際のAPIでは地域別エンドポイントが必要
        usa: 'https://thispersondoesnotexist.com/',
        europe: 'https://thispersondoesnotexist.com/',
        asia: 'https://thispersondoesnotexist.com/',
        mixed: 'https://thispersondoesnotexist.com/'
    };
    
    // 一時的に画像URLをキャッシュする
    let imageCache = {};
    
    /**
     * 特定の地域の顔画像URLを取得する
     * @param {string} region - 取得する地域 ('japan', 'usa', 'europe', 'asia', 'mixed')
     * @param {number} count - 取得する画像の数
     * @returns {Promise<Array>} - 画像URLの配列を含むPromise
     */
    async function getFaceImages(region, count) {
        // キャッシュされた画像があればそれを使用
        const cacheKey = `${region}_${count}`;
        if (imageCache[cacheKey]) {
            return [...imageCache[cacheKey]]; // キャッシュのコピーを返す
        }
        
        // 実際のAPIではここで適切なパラメータを使用してリクエストを行う
        // このデモバージョンでは、実際のAPIリクエストの代わりにモック画像を使用
        
        try {
            // 実際のアプリでは、ここでAPIリクエストを行う
            // const response = await fetch(`${API_ENDPOINTS[region]}?count=${count}`);
            // const data = await response.json();
            
            // デモ用のモックデータを作成
            const images = [];
            for (let i = 0; i < count; i++) {
                // タイムスタンプを追加してキャッシュを回避
                const timestamp = new Date().getTime() + i;
                // 地域に基づいて小さなバリエーションを加える
                const regionParam = region === 'mixed' ? ['japan', 'usa', 'europe', 'asia'][i % 4] : region;
                const imageUrl = `${API_ENDPOINTS[regionParam]}?v=${timestamp}`;
                images.push({
                    id: `face_${regionParam}_${i}`,
                    url: imageUrl,
                    region: regionParam
                });
            }
            
            // 結果をキャッシュ
            imageCache[cacheKey] = [...images];
            
            return images;
        } catch (error) {
            console.error('Error fetching face images:', error);
            return [];
        }
    }
    
    /**
     * キャッシュをクリアする
     */
    function clearCache() {
        imageCache = {};
    }
    
    /**
     * 指定された地域と性別に基づいて1つの顔画像URLを取得する
     * @param {string} region - 取得する地域
     * @param {string} gender - 性別 ('male', 'female', 'any')
     * @returns {Promise<Object>} - 画像情報を含むPromise
     */
    async function getSingleFaceImage(region, gender = 'any') {
        try {
            // ランダムなタイムスタンプを使用して新しい画像を取得
            const timestamp = new Date().getTime();
            
            // 実際のアプリでは、性別や地域を指定するパラメータを使用
            // const response = await fetch(`${API_ENDPOINTS[region]}?gender=${gender}&t=${timestamp}`);
            
            // デモ用のモックデータ
            let actualRegion = region;
            if (region === 'mixed') {
                const regions = ['japan', 'usa', 'europe', 'asia'];
                actualRegion = regions[Math.floor(Math.random() * regions.length)];
            }
            
            return {
                id: `face_${actualRegion}_${timestamp}`,
                url: `${API_ENDPOINTS[actualRegion]}?v=${timestamp}`,
                region: actualRegion
            };
        } catch (error) {
            console.error('Error fetching single face image:', error);
            return null;
        }
    }
    
    // 公開API
    return {
        getFaceImages,
        getSingleFaceImage,
        clearCache
    };
})();
