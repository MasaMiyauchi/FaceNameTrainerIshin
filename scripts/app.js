/**
 * FaceNameTrainer - メインアプリケーションスクリプト
 * 顔と名前の記憶トレーニングアプリケーションのコア機能を提供
 */

// DOMが完全に読み込まれた後に実行
document.addEventListener('DOMContentLoaded', function() {
    // アプリケーション状態
    const appState = {
        currentScreen: 'welcome-screen',  // 現在表示している画面
        selectedDifficulty: 'easy',       // 選択された難易度
        selectedRegion: 'japan',          // 選択された地域
        personCount: 5,                   // 記憶する人数
        learningTimer: null,              // 学習タイマーのインターバルID
        learningTime: 60,                 // 学習時間（秒）
        currentFaces: [],                 // 現在のセッションの顔データ
        testProgress: 0,                  // テスト進捗（何人目か）
        testResults: [],                  // テスト結果
        currentTestFace: null             // 現在テスト中の顔
    };

    // 初期化時にユーザー設定をロード
    loadSettings();

    // 初期化
    init();

    /**
     * アプリケーションの初期化
     */
    function init() {
        // イベントリスナーの設定
        setupEventListeners();
        
        // 最初は歓迎画面を表示
        showScreen('welcome-screen');
        
        // 難易度ボタンの初期選択状態
        updateDifficultyButtonsState();
        
        // 地域ボタンの初期選択状態
        updateRegionButtonsState();
    }

    /**
     * ユーザー設定をロードして適用
     */
    function loadSettings() {
        // 保存されていた設定をロード
        const settings = StorageModule.loadUserSettings();
        
        // 設定をアプリケーション状態に適用
        appState.selectedDifficulty = settings.difficulty || 'easy';
        appState.selectedRegion = settings.region || 'japan';
        appState.learningTime = settings.timerDuration || 60;
        appState.personCount = difficultyPersonCount[appState.selectedDifficulty];
    }

    // 画面を切り替える関数
    function showScreen(screenId) {
        // すべての画面を非表示
        document.querySelectorAll('.screen').forEach(screen => {
            screen.classList.remove('active');
        });
        
        // 指定した画面を表示
        document.getElementById(screenId).classList.add('active');
        
        // 現在の画面を更新
        appState.currentScreen = screenId;
    }

    // -- ここから下はそれぞれの機能の実装が続きます --
}); 
