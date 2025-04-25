/**
 * 各国/地域の名前データベース
 * 国や地域ごとに名と姓のリストを定義し、
 * ランダムに組み合わせて名前を生成するためのモジュール
 */

// 名前データの定義
const NAMES_DATABASE = {
    // 日本の名前データ
    japan: {
        // 名前（名）のリスト
        firstNames: [
            "翔太", "健太", "大輔", "拓海", "悠斗", "誠", "直樹", "浩二", "隆", "和也",
            "美咲", "さくら", "陽子", "彩花", "優子", "愛", "麻衣", "智子", "裕美", "恵"
        ],
        // 名前（姓）のリスト
        lastNames: [
            "佐藤", "鈴木", "高橋", "田中", "渡辺", "伊藤", "山本", "中村", "小林", "加藤",
            "吉田", "山田", "佐々木", "山口", "松本", "井上", "木村", "林", "斎藤", "清水"
        ],
        // 名前の生成方法（姓 + 名の形式）
        formatName: function(lastName, firstName) {
            return lastName + " " + firstName;
        }
    },
    
    // アメリカの名前データ
    usa: {
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
    },
    
    // ヨーロッパの名前データ
    europe: {
        // 名前（名）のリスト
        firstNames: [
            // フランス、ドイツ、イタリア、スペイン、イギリスなどの名前
            "Jean", "Pierre", "Thomas", "François", "Nicolas", "Hans", "Klaus", "Stefan", "Andreas", "Wolfgang",
            "Marco", "Giuseppe", "Antonio", "Giovanni", "Roberto", "Javier", "Carlos", "Miguel", "Alejandro", "David",
            "Sophie", "Emma", "Charlotte", "Lucie", "Marie", "Anna", "Laura", "Hannah", "Julia", "Katharina",
            "Francesca", "Chiara", "Valentina", "Sofia", "Giulia", "Carmen", "Sofia", "Lucia", "Maria", "Isabel"
        ],
        // 名前（姓）のリスト
        lastNames: [
            // フランス、ドイツ、イタリア、スペイン、イギリスなどの姓
            "Martin", "Bernard", "Dubois", "Thomas", "Robert", "Müller", "Schmidt", "Schneider", "Fischer", "Weber",
            "Rossi", "Ferrari", "Esposito", "Bianchi", "Romano", "Garcia", "Rodriguez", "Fernandez", "Martinez", "Lopez",
            "Smith", "Jones", "Taylor", "Brown", "Williams", "Davies", "Evans", "Wilson", "Thomas", "Roberts"
        ],
        // 名前の生成方法（名 + 姓の形式）
        formatName: function(lastName, firstName) {
            return firstName + " " + lastName;
        }
    },
    
    // アジア（日本除く）の名前データ
    asia: {
        // 名前（名）のリスト
        firstNames: [
            // 中国、韓国、東南アジアなどの名前
            "Wei", "Jie", "Ming", "Li", "Hao", "Min-jun", "Ji-hoon", "Seung-ho", "Joon-ho", "Hyun-woo",
            "Mei", "Xiu", "Jing", "Na", "Ying", "Min-ji", "Ji-young", "Seo-yeon", "Hye-jin", "Eun-ji",
            "Rizal", "Anwar", "Putra", "Budi", "Agus", "Dewi", "Siti", "Rina", "Putri", "Lestari"
        ],
        // 名前（姓）のリスト
        lastNames: [
            // 中国、韓国、東南アジアなどの姓
            "Wang", "Li", "Zhang", "Liu", "Chen", "Kim", "Lee", "Park", "Choi", "Jung",
            "Nguyen", "Tran", "Le", "Pham", "Hoang", "Suarez", "Reyes", "Santos", "Cruz", "Tan"
        ],
        // 名前の生成方法（姓 + 名の形式または名 + 姓の形式）
        formatName: function(lastName, firstName) {
            // ランダムに形式を変える（中国、韓国など姓が先の場合と東南アジアなど名が先の場合）
            return Math.random() > 0.5 ? lastName + " " + firstName : firstName + " " + lastName;
        }
    }
};

/**
 * 指定された地域に基づいて名前リストを生成する
 * @param {string} region - 生成する地域 ('japan', 'usa', 'europe', 'asia', 'mixed')
 * @param {number} count - 生成する名前の数
 * @returns {Array} - 生成された名前のリスト
 */
function generateNames(region, count) {
    // 'mixed'の場合は複数地域からランダムに選択
    if (region === 'mixed') {
        const regions = Object.keys(NAMES_DATABASE);
        const names = [];
        
        for (let i = 0; i < count; i++) {
            // ランダムに地域を選択
            const randomRegion = regions[Math.floor(Math.random() * regions.length)];
            names.push(generateSingleName(randomRegion));
        }
        
        return names;
    } else {
        // 特定の地域から名前を生成
        const names = [];
        for (let i = 0; i < count; i++) {
            names.push(generateSingleName(region));
        }
        return names;
    }
}

/**
 * 指定された地域から1つの名前を生成する
 * @param {string} region - 生成する地域 ('japan', 'usa', 'europe', 'asia')
 * @returns {string} - 生成された名前
 */
function generateSingleName(region) {
    const regionData = NAMES_DATABASE[region];
    
    // ランダムに姓と名を選択
    const lastName = regionData.lastNames[Math.floor(Math.random() * regionData.lastNames.length)];
    const firstName = regionData.firstNames[Math.floor(Math.random() * regionData.firstNames.length)];
    
    // 名前のフォーマット関数を使用して名前を生成
    return regionData.formatName(lastName, firstName);
}

// グローバルに公開するオブジェクト
const NamesModule = {
    generateNames,
    getRegions: function() {
        return Object.keys(NAMES_DATABASE).concat(['mixed']);
    }
};