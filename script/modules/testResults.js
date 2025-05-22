/**
 * テスト結果表示モジュール
 */

const loadingElement = document.getElementById('loading');
const resultsContainer = document.getElementById('results-container');
const correctCountElement = document.getElementById('correct-count');
const questionCountElement = document.getElementById('question-count');
const accuracyElement = document.getElementById('accuracy');
const resultDetailsElement = document.getElementById('result-details');
const retryButton = document.getElementById('retry-btn');
const homeButton = document.getElementById('home-btn');

/**
 * 初期化関数
 */
async function init() {
    try {
        const storedResults = sessionStorage.getItem('testResults');
        if (!storedResults) {
            alert('テスト結果が見つかりません。トップページに戻ります。');
            window.location.href = 'index.html';
            return;
        }
        
        const testResults = JSON.parse(storedResults);
        
        await loadResultDetails(testResults);
        
        loadingElement.style.display = 'none';
        resultsContainer.style.display = 'block';
        
        setupEventListeners();
        
    } catch (error) {
        alert(`エラーが発生しました: ${error.message}`);
        console.error('Error initializing test results:', error);
    }
}

/**
 * 結果の詳細を読み込む
 */
async function loadResultDetails(testResults) {
    correctCountElement.textContent = testResults.correctCount;
    questionCountElement.textContent = testResults.questionCount;
    
    const accuracy = Math.round((testResults.correctCount / testResults.questionCount) * 100);
    accuracyElement.textContent = accuracy;
    
    resultDetailsElement.innerHTML = '';
    
    for (const answer of testResults.answers) {
        const response = await fetch(`php/api/training.php?action=get_pair_by_id&id=${answer.faceId}`);
        const result = await response.json();
        
        if (!result.success) {
            console.error('Failed to load face data:', result.error);
            continue;
        }
        
        const faceData = result.data;
        
        const resultItem = document.createElement('div');
        resultItem.className = 'result-item';
        
        let statusText = answer.isCorrect ? '正解' : '不正解';
        let statusClass = answer.isCorrect ? 'correct' : 'incorrect';
        
        resultItem.innerHTML = `
            <div class="result-item-face">
                <img src="${faceData.image_uri}" alt="顔画像">
            </div>
            <div class="result-item-info">
                <div class="result-item-name">${faceData.family_name} ${faceData.given_name}</div>
                <div class="result-item-answer">
                    あなたの回答: ${answer.userAnswer}
                </div>
            </div>
            <div class="result-item-status ${statusClass}">${statusText}</div>
        `;
        
        resultDetailsElement.appendChild(resultItem);
    }
}

/**
 * イベントリスナーの設定
 */
function setupEventListeners() {
    retryButton.addEventListener('click', () => {
        window.location.href = 'test-settings.html';
    });
    
    homeButton.addEventListener('click', () => {
        window.location.href = 'index.html';
    });
}

document.addEventListener('DOMContentLoaded', init);
