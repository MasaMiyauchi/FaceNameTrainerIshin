/**
 * シンプル足し算アプリケーション
 * 2つの数値を受け取り、その合計を計算して表示するスクリプト
 */

// DOMが完全に読み込まれた後に実行
document.addEventListener('DOMContentLoaded', function() {
    // 要素の取得
    const number1Input = document.getElementById('number1');
    const number2Input = document.getElementById('number2');
    const calculateButton = document.getElementById('calculate-button');
    const resultDisplay = document.getElementById('result');

    // 計算ボタンのクリックイベント
    calculateButton.addEventListener('click', calculateSum);

    // Enterキーが押されたときにも計算を実行
    number1Input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') calculateSum();
    });
    
    number2Input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') calculateSum();
    });

    /**
     * 2つの入力値の合計を計算して表示する関数
     */
    function calculateSum() {
        // 入力値の取得
        const num1 = parseFloat(number1Input.value) || 0;
        const num2 = parseFloat(number2Input.value) || 0;
        
        // 合計の計算
        const sum = num1 + num2;
        
        // 結果の表示（小数点以下を適切に処理）
        resultDisplay.textContent = Number.isInteger(sum) ? sum : sum.toFixed(2);
        
        // 結果を強調表示するためのアニメーション効果
        resultDisplay.classList.add('highlight');
        
        // 少し経ったらハイライト効果を削除
        setTimeout(() => {
            resultDisplay.classList.remove('highlight');
        }, 300);
    }

    // 初期フォーカスを最初の入力フィールドに設定
    number1Input.focus();
});