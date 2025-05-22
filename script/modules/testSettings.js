/**
 * テスト設定モジュール
 */
document.addEventListener('DOMContentLoaded', () => {
    const storedFaceIds = sessionStorage.getItem('trainingFaceIds');
    if (!storedFaceIds) {
        alert('記憶した顔のデータがありません。トレーニングからやり直してください。');
        window.location.href = 'training-settings.html';
        return;
    }
    
    const faceIds = JSON.parse(storedFaceIds);
    const maxQuestions = faceIds.length;
    
    const questionCountInput = document.getElementById('question-count');
    const questionCountValue = document.getElementById('question-count-value');
    
    questionCountInput.max = maxQuestions;
    if (parseInt(questionCountInput.value) > maxQuestions) {
        questionCountInput.value = maxQuestions;
    }
    
    questionCountValue.textContent = `${questionCountInput.value}問`;
    
    questionCountInput.addEventListener('input', () => {
        questionCountValue.textContent = `${questionCountInput.value}問`;
    });
    
    const testForm = document.getElementById('test-form');
    testForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const course = document.querySelector('input[name="test-course"]:checked').value;
        const questionCount = parseInt(questionCountInput.value);
        
        sessionStorage.setItem('testSettings', JSON.stringify({
            course,
            questionCount,
            faceIds: faceIds.slice(0, questionCount)
        }));
        
        window.location.href = course === 'a' ? 'test-a.html' : 'test-b.html';
    });
    
    const cancelBtn = document.getElementById('cancel-btn');
    cancelBtn.addEventListener('click', () => {
        if (confirm('テスト設定をキャンセルしますか？')) {
            window.location.href = 'index.html';
        }
    });
});
