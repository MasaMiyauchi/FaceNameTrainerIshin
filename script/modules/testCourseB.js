/**
 * テストモード（Bコース：顔→名前入力）
 */

let testSettings = null;
let faceData = [];
let currentQuestionIndex = 0;
let userAnswers = [];
let isAnswered = false;

const loadingElement = document.getElementById('loading');
const testContainer = document.getElementById('test-container');
const progressElement = document.getElementById('progress');
const currentQuestionElement = document.getElementById('current-question');
const totalQuestionsElement = document.getElementById('total-questions');
const targetFaceElement = document.getElementById('target-face');
const nameInputElement = document.getElementById('name-input');
const nameFeedbackElement = document.getElementById('name-feedback');
const checkButton = document.getElementById('check-btn');
const nextButton = document.getElementById('next-btn');
const cancelButton = document.getElementById('cancel-btn');

/**
 * 初期化関数
 */
async function init() {
    try {
        const storedSettings = sessionStorage.getItem('testSettings');
        if (!storedSettings) {
            alert('テスト設定が見つかりません。設定画面からやり直してください。');
            window.location.href = 'test-settings.html';
            return;
        }
        
        testSettings = JSON.parse(storedSettings);
        
        await loadFaceData();
        
        loadingElement.style.display = 'none';
        testContainer.style.display = 'block';
        
        totalQuestionsElement.textContent = testSettings.questionCount;
        
        showQuestion(0);
        
        setupEventListeners();
        
    } catch (error) {
        alert(`エラーが発生しました: ${error.message}`);
        console.error('Error initializing test mode B:', error);
    }
}

/**
 * 顔データを読み込む
 */
async function loadFaceData() {
    const faceIds = testSettings.faceIds;
    
    const fetchPromises = faceIds.map(id => 
        fetch(`php/api/training.php?action=get_pair_by_id&id=${id}`)
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.error || 'データの取得に失敗しました');
                }
                return result.data;
            })
    );
    
    faceData = await Promise.all(fetchPromises);
}

/**
 * 問題を表示
 */
function showQuestion(index) {
    if (index >= faceData.length) {
        finishTest();
        return;
    }
    
    currentQuestionIndex = index;
    isAnswered = false;
    
    updateProgress();
    
    currentQuestionElement.textContent = index + 1;
    
    const currentFace = faceData[index];
    targetFaceElement.src = currentFace.image_uri;
    
    nameInputElement.value = '';
    nameInputElement.classList.remove('correct', 'incorrect');
    nameInputElement.disabled = false;
    nameInputElement.focus();
    
    nameFeedbackElement.textContent = '';
    nameFeedbackElement.classList.remove('correct', 'incorrect');
    nameFeedbackElement.style.display = 'none';
    
    checkButton.style.display = 'block';
    nextButton.style.display = 'none';
}

/**
 * 回答をチェック
 */
function checkAnswer() {
    if (isAnswered) return;
    
    const userInput = nameInputElement.value.trim();
    if (!userInput) {
        alert('名前を入力してください');
        return;
    }
    
    isAnswered = true;
    nameInputElement.disabled = true;
    
    const currentFace = faceData[currentQuestionIndex];
    const correctName = `${currentFace.family_name} ${currentFace.given_name}`;
    
    const result = validateAnswer(userInput, correctName);
    
    if (result.isCorrect) {
        nameInputElement.classList.add('correct');
        nameFeedbackElement.textContent = '正解です！';
        nameFeedbackElement.classList.add('correct');
        nameFeedbackElement.classList.remove('incorrect');
    } else {
        nameInputElement.classList.add('incorrect');
        nameFeedbackElement.textContent = `不正解です。正解は「${correctName}」です。`;
        nameFeedbackElement.classList.add('incorrect');
        nameFeedbackElement.classList.remove('correct');
    }
    
    nameFeedbackElement.style.display = 'block';
    
    userAnswers[currentQuestionIndex] = {
        questionIndex: currentQuestionIndex,
        faceId: currentFace.id,
        correctAnswer: correctName,
        userAnswer: userInput,
        isCorrect: result.isCorrect,
        matchLevel: result.matchLevel
    };
    
    checkButton.style.display = 'none';
    nextButton.style.display = 'block';
}

/**
 * 回答の正誤を判定
 */
function validateAnswer(userInput, correctAnswer) {
    if (userInput === correctAnswer) {
        return { isCorrect: true, matchLevel: 'exact' };
    }
    
    const normalizedUserInput = userInput.replace(/\s+/g, '');
    const normalizedCorrectAnswer = correctAnswer.replace(/\s+/g, '');
    
    if (normalizedUserInput === normalizedCorrectAnswer) {
        return { isCorrect: true, matchLevel: 'normalized' };
    }
    
    const correctParts = correctAnswer.split(' ');
    if (correctParts.length === 2) {
        const [correctFamily, correctGiven] = correctParts;
        
        if (userInput === correctFamily) {
            return { isCorrect: false, matchLevel: 'family_only' };
        }
        
        if (userInput === correctGiven) {
            return { isCorrect: false, matchLevel: 'given_only' };
        }
    }
    
    return { isCorrect: false, matchLevel: 'none' };
}

/**
 * 進捗バーを更新
 */
function updateProgress() {
    const progressPercent = ((currentQuestionIndex + 1) / faceData.length) * 100;
    progressElement.style.width = `${progressPercent}%`;
}

/**
 * テストを終了し、結果画面に遷移
 */
function finishTest() {
    sessionStorage.setItem('testResults', JSON.stringify({
        course: 'b',
        questionCount: faceData.length,
        correctCount: userAnswers.filter(answer => answer.isCorrect).length,
        answers: userAnswers
    }));
    
    window.location.href = 'test-results.html';
}

/**
 * イベントリスナーの設定
 */
function setupEventListeners() {
    checkButton.addEventListener('click', checkAnswer);
    
    nameInputElement.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !isAnswered) {
            checkAnswer();
        }
    });
    
    nextButton.addEventListener('click', () => {
        showQuestion(currentQuestionIndex + 1);
    });
    
    cancelButton.addEventListener('click', () => {
        if (confirm('テストをキャンセルしますか？現在の進捗は失われます。')) {
            window.location.href = 'index.html';
        }
    });
}

document.addEventListener('DOMContentLoaded', init);
