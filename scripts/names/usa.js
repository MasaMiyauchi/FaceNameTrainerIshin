/**
 * アメリカ合衆国の名前データ
 */

// アメリカの名前データ
const usaNames = {
    // 名前（名）のリスト
    firstNames: [
        "James", "Robert", "John", "Michael", "William", "David", "Richard", "Joseph", "Thomas", "Christopher",
        "Mary", "Patricia", "Jennifer", "Linda", "Elizabeth", "Susan", "Jessica", "Sarah", "Karen", "Nancy"
    ],
    // 名前（姓）のリスト
    lastNames: [
        "Smith", "Johnson", "Williams", "Brown", "Jones", "Garcia", "Miller", "Davis", "Rodriguez", "Martinez",
        "Hernandez", "Lopez", "Gonzalez", "Wilson", "Anderson", "Thomas", "Taylor", "Moore", "Jackson", "Martin"
    ],
    // 名前の生成方法（名 + 姓の形式）
    formatName: function(lastName, firstName) {
        return firstName + " " + lastName;
    }
};

// モジュールのエクスポート
export default usaNames;