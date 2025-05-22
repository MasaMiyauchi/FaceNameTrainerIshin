/**
 * テストモード（Aコース：名前→顔選択）
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
const targetNameElement = document.getElementById('target-name');
const faceOptionsElement = document.getElementById('face-options');
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
        console.error('Error initializing test mode A:', error);
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
    
    const faces = await Promise.all(fetchPromises);
    faceData = faces;
    
    for (let i = 0; i < faceData.length; i++) {
        let otherOptions = getRandomOtherFaces(i, 2);
        faceData[i].options = shuffleArray([
            { face: faceData[i], isCorrect: true },
            ...otherOptions.map(face => ({ face, isCorrect: false }))
        ]);
    }
}

/**
 * 現在のインデックス以外からランダムな顔を取得
 */
function getRandomOtherFaces(currentIndex, count) {
    const otherFaces = faceData.filter((_, index) => index !== currentIndex);
    return shuffleArray([...otherFaces]).slice(0, count);
}

/**
 * 配列をシャッフル
 */
function shuffleArray(array) {
    const newArray = [...array];
    for (let i = newArray.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
    }
    return newArray;
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
    targetNameElement.textContent = `${currentFace.family_name} ${currentFace.given_name}`;
    
    faceOptionsElement.innerHTML = '';
    
    currentFace.options.forEach((option, optionIndex) => {
        const faceOption = document.createElement('div');
        faceOption.className = 'face-option';
        faceOption.dataset.index = optionIndex;
        
        faceOption.innerHTML = `
            <span class="face-option-label">${optionIndex + 1}</span>
            <img src="${option.face.image_uri}" alt="顔の選択肢" class="face-option-img">
        `;
        
        faceOption.addEventListener('click', () => {
            if (isAnswered) return;
            
            selectOption(optionIndex);
        });
        
        faceOptionsElement.appendChild(faceOption);
    });
    
    nextButton.disabled = true;
}

/**
 * 選択肢を選択
 */
function selectOption(optionIndex) {
    isAnswered = true;
    const selectedOption = faceData[currentQuestionIndex].options[optionIndex];
    
    const options = faceOptionsElement.querySelectorAll('.face-option');
    options.forEach(option => {
        option.classList.remove('selected', 'correct', 'incorrect');
    });
    
    options[optionIndex].classList.add('selected');
    
    if (selectedOption.isCorrect) {
        options[optionIndex].classList.add('correct');
    } else {
        options[optionIndex].classList.add('incorrect');
        
        const correctIndex = faceData[currentQuestionIndex].options.findIndex(option => option.isCorrect);
        options[correctIndex].classList.add('correct');
    }
    
    userAnswers[currentQuestionIndex] = {
        questionIndex: currentQuestionIndex,
        faceId: faceData[currentQuestionIndex].id,
        correctAnswer: `${faceData[currentQuestionIndex].family_name} ${faceData[currentQuestionIndex].given_name}`,
        userAnswer: `選択肢${optionIndex + 1}`,
        isCorrect: selectedOption.isCorrect
    };
    
    nextButton.disabled = false;
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
        course: 'a',
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
