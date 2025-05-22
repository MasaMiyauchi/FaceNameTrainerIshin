/**
 * 記憶モード制御モジュール
 */

const urlParams = new URLSearchParams(window.location.search);
const personCount = parseInt(urlParams.get('count')) || 5;
const displayTime = parseInt(urlParams.get('time')) || 10;

let faceNameData = [];
let currentIndex = 0;
let timer = null;
let timeRemaining = displayTime;
let isPaused = false;

const loadingElement = document.getElementById('loading');
const memoryContainer = document.getElementById('memory-container');
const completionElement = document.getElementById('completion');
const faceImage = document.getElementById('face-image');
const fullNameElement = document.getElementById('full-name');
const ageGenderElement = document.getElementById('age-gender');
const timerElement = document.getElementById('timer');
const progressElement = document.getElementById('progress');
const prevButton = document.getElementById('prev-btn');
const nextButton = document.getElementById('next-btn');
const pauseButton = document.getElementById('pause-btn');
const testButton = document.getElementById('test-btn');
const reviewButton = document.getElementById('review-btn');
const cancelButton = document.getElementById('cancel-btn');

/**
 * 初期化関数
 */
async function init() {
    try {
        const response = await fetch(`php/api/training.php?action=get_random_pairs&count=${personCount}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || '顔と名前のデータを取得できませんでした');
        }
        
        faceNameData = result.data;
        
        loadingElement.style.display = 'none';
        memoryContainer.style.display = 'block';
        
        showCurrentFaceAndName();
        startTimer();
        
        setupEventListeners();
        
    } catch (error) {
        alert(`エラーが発生しました: ${error.message}`);
        console.error('Error initializing memory mode:', error);
    }
}

/**
 * 現在の顔と名前を表示
 */
function showCurrentFaceAndName() {
    if (currentIndex >= faceNameData.length) {
        showCompletion();
        return;
    }
    
    const currentData = faceNameData[currentIndex];
    
    faceImage.src = currentData.image_uri;
    fullNameElement.textContent = `${currentData.family_name} ${currentData.given_name}`;
    ageGenderElement.textContent = `${currentData.age}歳・${currentData.gender === 'male' ? '男性' : '女性'}`;
    
    updateProgress();
    
    prevButton.disabled = currentIndex === 0;
    nextButton.disabled = currentIndex === faceNameData.length - 1;
    
    resetTimer();
}

/**
 * プログレスバーを更新
 */
function updateProgress() {
    const progressPercent = ((currentIndex + 1) / faceNameData.length) * 100;
    progressElement.style.width = `${progressPercent}%`;
}

/**
 * タイマーを開始
 */
function startTimer() {
    if (timer) clearInterval(timer);
    
    timerElement.textContent = timeRemaining;
    
    timer = setInterval(() => {
        if (isPaused) return;
        
        timeRemaining--;
        timerElement.textContent = timeRemaining;
        
        if (timeRemaining <= 0) {
            clearInterval(timer);
            
            if (currentIndex === faceNameData.length - 1) {
                showCompletion();
            } else {
                currentIndex++;
                showCurrentFaceAndName();
            }
        }
    }, 1000);
}

/**
 * タイマーをリセット
 */
function resetTimer() {
    if (timer) clearInterval(timer);
    timeRemaining = displayTime;
    timerElement.textContent = timeRemaining;
    startTimer();
}

/**
 * 完了画面を表示
 */
function showCompletion() {
    if (timer) clearInterval(timer);
    memoryContainer.style.display = 'none';
    completionElement.style.display = 'block';
}

/**
 * イベントリスナーの設定
 */
function setupEventListeners() {
    prevButton.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            showCurrentFaceAndName();
        }
    });
    
    nextButton.addEventListener('click', () => {
        if (currentIndex < faceNameData.length - 1) {
            currentIndex++;
            showCurrentFaceAndName();
        } else {
            showCompletion();
        }
    });
    
    pauseButton.addEventListener('click', () => {
        isPaused = !isPaused;
        pauseButton.textContent = isPaused ? '再開' : '一時停止';
    });
    
    testButton.addEventListener('click', () => {
        const faceIds = faceNameData.map(face => face.id);
        sessionStorage.setItem('trainingFaceIds', JSON.stringify(faceIds));
        
        window.location.href = 'test-settings.html';
    });
    
    reviewButton.addEventListener('click', () => {
        currentIndex = 0;
        completionElement.style.display = 'none';
        memoryContainer.style.display = 'block';
        showCurrentFaceAndName();
    });
    
    cancelButton.addEventListener('click', () => {
        if (confirm('トレーニングをキャンセルしますか？')) {
            window.location.href = 'index.html';
        }
    });
}

document.addEventListener('DOMContentLoaded', init);
