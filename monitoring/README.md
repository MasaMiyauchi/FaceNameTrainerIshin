# Monitoring ディレクトリ

このディレクトリには、FaceNameTrainerIshinアプリケーションのモニタリングツールが含まれています。

## 目的

アプリケーションの各コンポーネントのパフォーマンス、品質、エラー率などを監視し、システムの正常性を確認するためのツールを提供します。

## 構成

- **generateImage/** - 画像生成モニタリングツール
  - `README.md` - 画像生成モニタリングツールの詳細な仕様
  - `test.php` - 画像生成機能のテストスクリプト
  - `php/` - PHP実装
    - `ImageGenerator.php` - 顔画像生成クラス
    - `ImageMonitor.php` - 画像生成モニタリングクラス
  - `js/` - JavaScript実装
    - `imageGenerator.js` - 顔画像生成関数
    - `imageMonitor.js` - 画像生成モニタリング関数
    - `index.js` - メインエントリーポイント

## 機能

1. **パフォーマンスモニタリング**
   - 応答時間測定
   - スループット計測
   - リソース使用率追跡

2. **品質モニタリング**
   - 生成画像の品質評価
   - プロンプト準拠度チェック
   - 多様性スコア計算

3. **エラー監視**
   - エラー率の追跡
   - エラータイプの分類
   - リトライ回数の記録

4. **レポート生成**
   - 日次/週次レポート
   - アラート通知
   - 傾向分析

## 使用方法

モニタリングツールは以下の方法で使用できます：

1. **テストスクリプトの実行**
   ```
   http://localhost/monitoring/generateImage/test.php
   ```

2. **APIとしての利用**
   ```php
   require_once 'monitoring/generateImage/php/ImageGenerator.php';
   require_once 'monitoring/generateImage/php/ImageMonitor.php';
   
   $generator = new ImageGenerator();
   $monitor = new ImageMonitor();
   
   $result = $monitor->monitorImageGeneration($generator, [
     'age' => 30,
     'gender' => 'female'
   ]);
   ```

3. **バッチモニタリング**
   ```php
   $batchResults = $monitor->monitorBatchGeneration(10, [
     'ageRange' => [20, 70],
     'genderDistribution' => ['male' => 0.5, 'female' => 0.5]
   ]);
   ```

## 拡張計画

1. **ダッシュボード開発** - モニタリングデータの可視化インターフェース
2. **アラート機能強化** - 異常検知と通知システムの改善
3. **追加コンポーネントモニタリング** - 名前生成、ユーザーセッションなどの監視
