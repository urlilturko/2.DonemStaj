<?php
session_start();

// Game configuration constants
$DIFFICULTIES = [
    'easy' => [
        'label' => 'Kolay',
        'min' => 1,
        'max' => 50,
        'max_attempts' => 10,
        'color' => '#10b981', // Emerald Green
        'glow' => 'rgba(16, 185, 129, 0.4)'
    ],
    'medium' => [
        'label' => 'Orta',
        'min' => 1,
        'max' => 100,
        'max_attempts' => 7,
        'color' => '#f59e0b', // Amber
        'glow' => 'rgba(245, 158, 11, 0.4)'
    ],
    'hard' => [
        'label' => 'Zor',
        'min' => 1,
        'max' => 250,
        'max_attempts' => 5,
        'color' => '#ef4444', // Red
        'glow' => 'rgba(239, 68, 68, 0.4)'
    ]
];

// Initialize global stats if they don't exist
if (!isset($_SESSION['streak'])) {
    $_SESSION['streak'] = 0;
}
if (!isset($_SESSION['best_scores'])) {
    $_SESSION['best_scores'] = [
        'easy' => null,
        'medium' => null,
        'hard' => null
    ];
}

// Current game difficulty setting
if (!isset($_SESSION['difficulty'])) {
    $_SESSION['difficulty'] = 'medium';
}

$current_diff = $_SESSION['difficulty'];
$diff_settings = $DIFFICULTIES[$current_diff];

// Initialize game state function
function start_new_game($diff) {
    global $DIFFICULTIES;
    $settings = $DIFFICULTIES[$diff];
    $_SESSION['difficulty'] = $diff;
    $_SESSION['secret_number'] = rand($settings['min'], $settings['max']);
    $_SESSION['max_attempts'] = $settings['max_attempts'];
    $_SESSION['attempts_left'] = $settings['max_attempts'];
    $_SESSION['history'] = [];
    $_SESSION['game_status'] = 'playing';
}

// If game is not initialized, initialize it
if (!isset($_SESSION['game_status']) || !isset($_SESSION['secret_number'])) {
    start_new_game($current_diff);
}

$feedback = '';
$feedback_type = ''; // 'higher', 'lower', 'correct', 'error', 'lost'
$shake_input = false;

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'change_difficulty' && isset($_POST['difficulty'])) {
            $new_diff = $_POST['difficulty'];
            if (array_key_exists($new_diff, $DIFFICULTIES)) {
                start_new_game($new_diff);
                header("Location: index.php");
                exit;
            }
        }
        
        if ($action === 'reset') {
            start_new_game($_SESSION['difficulty']);
            header("Location: index.php");
            exit;
        }
        
        if ($action === 'reset_stats') {
            $_SESSION['streak'] = 0;
            $_SESSION['best_scores'] = [
                'easy' => null,
                'medium' => null,
                'hard' => null
            ];
            start_new_game($_SESSION['difficulty']);
            header("Location: index.php");
            exit;
        }
        
        if ($action === 'guess' && $_SESSION['game_status'] === 'playing') {
            if (isset($_POST['guess']) && trim($_POST['guess']) !== '') {
                $guess = (int)$_POST['guess'];
                $min = $diff_settings['min'];
                $max = $diff_settings['max'];
                
                if ($guess < $min || $guess > $max) {
                    $feedback = "Lütfen {$min} ile {$max} arasında geçerli bir sayı girin!";
                    $feedback_type = 'error';
                    $shake_input = true;
                } else {
                    // Check if guess already exists in history to prevent double attempts
                    $already_guessed = false;
                    foreach ($_SESSION['history'] as $h) {
                        if ($h['guess'] === $guess) {
                            $already_guessed = true;
                            break;
                        }
                    }
                    
                    if ($already_guessed) {
                        $feedback = "Bu sayıyı daha önce tahmin ettiniz!";
                        $feedback_type = 'error';
                        $shake_input = true;
                    } else {
                        $_SESSION['attempts_left']--;
                        $secret = $_SESSION['secret_number'];
                        
                        if ($guess === $secret) {
                            $_SESSION['game_status'] = 'won';
                            $_SESSION['streak']++;
                            $attempts_used = $_SESSION['max_attempts'] - $_SESSION['attempts_left'];
                            
                            $prev_best = $_SESSION['best_scores'][$current_diff];
                            if ($prev_best === null || $attempts_used < $prev_best) {
                                $_SESSION['best_scores'][$current_diff] = $attempts_used;
                            }
                            
                            $_SESSION['history'][] = [
                                'guess' => $guess,
                                'result' => 'correct',
                                'hint' => 'Tebrikler! 🎉'
                            ];
                            $feedback = "Tebrikler! Doğru sayı: <strong>{$secret}</strong>.";
                            $feedback_type = 'correct';
                        } else {
                            $hint = ($guess < $secret) ? 'higher' : 'lower';
                            $_SESSION['history'][] = [
                                'guess' => $guess,
                                'result' => $hint,
                                'hint' => ($hint === 'higher') ? 'Daha Yüksek ⬆️' : 'Daha Düşük ⬇️'
                            ];
                            
                            if ($_SESSION['attempts_left'] <= 0) {
                                $_SESSION['game_status'] = 'lost';
                                $_SESSION['streak'] = 0; // Reset win streak
                                $feedback = "Kaybettiniz! Doğru sayı <strong>{$secret}</strong> idi.";
                                $feedback_type = 'lost';
                            } else {
                                $feedback = ($hint === 'higher') ? "Daha yüksek bir sayı girmelisiniz!" : "Daha düşük bir sayı girmelisiniz!";
                                $feedback_type = $hint;
                            }
                        }
                    }
                }
            } else {
                $feedback = "Lütfen bir tahminde bulunun!";
                $feedback_type = 'error';
                $shake_input = true;
            }
        }
    }
}

// Re-read current states
$attempts_left = $_SESSION['attempts_left'];
$max_attempts = $_SESSION['max_attempts'];
$game_status = $_SESSION['game_status'];
$history = array_reverse($_SESSION['history']); // Show newest guess first
$streak = $_SESSION['streak'];
$best_score = $_SESSION['best_scores'][$current_diff];
$progress_pct = ($attempts_left / $max_attempts) * 100;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sayı Tahmin Oyunu - PHP</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Color Palette & Custom Properties */
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(17, 24, 39, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --primary-hover: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
            --accent-glow: rgba(99, 102, 241, 0.15);
            
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        /* Reset & Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(245, 158, 11, 0.1) 0%, transparent 40%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Premium Container & Cards */
        .container {
            width: 100%;
            max-width: 500px;
            margin: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
            animation: fadeIn 0.6s ease-out;
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -50%;
            width: 200%;
            height: 100%;
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.02) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: skewX(-25deg);
            transition: 0.75s;
            pointer-events: none;
        }

        .glass-card:hover::before {
            left: 120%;
        }

        /* Typography */
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            text-align: center;
            background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            text-align: center;
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-icon {
            font-size: 1.5rem;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(99, 102, 241, 0.1);
            color: #818cf8;
        }

        .stat-icon.streak {
            background: rgba(245, 158, 11, 0.1);
            color: #fbbf24;
        }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Difficulty Selector */
        .difficulty-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 24px;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .diff-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .diff-btn {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 10px;
            color: var(--text-secondary);
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .diff-btn span {
            font-size: 0.7rem;
            font-weight: 400;
            opacity: 0.7;
        }

        .diff-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-primary);
            transform: translateY(-2px);
        }

        .diff-btn.active[data-diff="easy"] {
            background: rgba(16, 185, 129, 0.15);
            border-color: var(--success);
            color: var(--success);
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.2);
        }

        .diff-btn.active[data-diff="medium"] {
            background: rgba(245, 158, 11, 0.15);
            border-color: var(--warning);
            color: var(--warning);
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.2);
        }

        .diff-btn.active[data-diff="hard"] {
            background: rgba(239, 68, 68, 0.15);
            border-color: var(--danger);
            color: var(--danger);
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.2);
        }

        /* Guess Section Form */
        .guess-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .game-instruction {
            font-size: 1.05rem;
            text-align: center;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .game-instruction span {
            color: <?php echo $diff_settings['color']; ?>;
            font-weight: 700;
            text-shadow: 0 0 10px <?php echo $diff_settings['glow']; ?>;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: stretch;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 4px;
            transition: all 0.3s;
        }

        .input-group:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.25);
            background: rgba(255, 255, 255, 0.05);
        }

        .guess-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            padding: 12px 16px;
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
        }

        /* Hide HTML5 Number Spinners */
        .guess-input::-webkit-outer-spin-button,
        .guess-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .guess-input[type=number] {
            -moz-appearance: textfield;
        }

        .guess-btn {
            background: var(--primary-gradient);
            border: none;
            outline: none;
            border-radius: 12px;
            color: white;
            padding: 0 24px;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .guess-btn:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }

        .guess-btn:active {
            transform: translateY(1px);
        }

        /* Progress Bar */
        .progress-container {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .progress-track {
            background: rgba(255, 255, 255, 0.05);
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .progress-bar {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.5s ease;
        }

        /* Feedback Alerts */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 16px;
            font-size: 0.95rem;
            margin-top: 16px;
            border: 1px solid transparent;
            animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .alert-higher {
            background: rgba(59, 130, 246, 0.12);
            border-color: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }

        .alert-lower {
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.2);
            color: #fde047;
        }

        /* Game Over Layout (Won / Lost) */
        .game-over-card {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 10px 0;
        }

        .result-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 8px;
            position: relative;
        }

        .result-icon.won {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
            animation: pulse-green 2s infinite;
        }

        .result-icon.lost {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
            animation: shake 0.5s ease-in-out;
        }

        .result-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
        }

        .result-message {
            font-size: 1.05rem;
            color: var(--text-secondary);
            max-width: 340px;
            line-height: 1.5;
        }

        .result-message strong {
            color: #ffffff;
            font-size: 1.25rem;
            background: rgba(255, 255, 255, 0.08);
            padding: 2px 10px;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            margin-top: 8px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            width: 100%;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Guesses History Logs */
        .history-card {
            max-height: 320px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .history-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow-y: auto;
            padding-right: 4px;
        }

        /* Customized scrollbar */
        .history-list::-webkit-scrollbar {
            width: 6px;
        }
        .history-list::-webkit-scrollbar-track {
            background: transparent;
        }
        .history-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .history-list::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .history-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 12px;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .history-num {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .history-badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-higher {
            background: rgba(59, 130, 246, 0.12);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .badge-lower {
            background: rgba(245, 158, 11, 0.12);
            color: #fde047;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .badge-correct {
            background: rgba(16, 185, 129, 0.12);
            color: #a7f3d0;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* Reset Stats Button */
        .footer-action {
            display: flex;
            justify-content: center;
            margin-top: 4px;
        }

        .reset-stats-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: underline;
            transition: color 0.2s;
            opacity: 0.6;
        }

        .reset-stats-btn:hover {
            color: var(--danger);
            opacity: 1;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-8px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 12px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .shake-element {
            animation: shake 0.4s ease-in-out;
        }
        
        /* Media Queries */
        @media (max-width: 480px) {
            .glass-card {
                padding: 20px;
                border-radius: 20px;
            }
            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- HEADER & STATS CARD -->
        <header class="glass-card">
            <h1>Sayı Tahmin Oyunu</h1>
            <p class="subtitle">Sunucunun tuttuğu gizli sayıyı bulabilir misin?</p>
            
            <div class="stats-grid">
                <div class="stat-box" title="Üst üste kazandığınız oyun sayısı">
                    <div class="stat-icon streak">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Seri (Streak)</span>
                        <span class="stat-value"><?php echo $streak; ?></span>
                    </div>
                </div>
                <div class="stat-box" title="Bu zorlukta en iyi (en az) deneme skorunuz">
                    <div class="stat-icon">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">En İyi Skor</span>
                        <span class="stat-value"><?php echo ($best_score !== null) ? $best_score . " Tahmin" : "Yok"; ?></span>
                    </div>
                </div>
            </div>

            <!-- Difficulty Selector Panel -->
            <div class="difficulty-container">
                <div class="section-title">Zorluk Seviyesi</div>
                <form id="diffForm" method="POST" action="index.php" class="diff-buttons">
                    <input type="hidden" name="action" value="change_difficulty">
                    <input type="hidden" name="difficulty" id="selectedDifficulty" value="<?php echo $current_diff; ?>">
                    
                    <?php foreach ($DIFFICULTIES as $key => $settings): ?>
                        <button type="button" 
                                class="diff-btn <?php echo ($current_diff === $key) ? 'active' : ''; ?>" 
                                data-diff="<?php echo $key; ?>"
                                onclick="changeDifficulty('<?php echo $key; ?>')">
                            <?php echo $settings['label']; ?>
                            <span><?php echo $settings['min'] . '-' . $settings['max']; ?></span>
                        </button>
                    <?php endforeach; ?>
                </form>
            </div>
        </header>

        <!-- MAIN GAME CARD -->
        <main class="glass-card">
            
            <?php if ($game_status === 'playing'): ?>
                <!-- PLAYING VIEW -->
                <form id="guessForm" method="POST" action="index.php" class="guess-form">
                    <input type="hidden" name="action" value="guess">
                    
                    <div class="game-instruction">
                        <span><?php echo $diff_settings['min'] . ' ile ' . $diff_settings['max']; ?></span> arasında bir sayı tahmin edin.
                    </div>
                    
                    <div class="input-group <?php echo $shake_input ? 'shake-element' : ''; ?>">
                        <input type="number" 
                               name="guess" 
                               id="guessInput" 
                               class="guess-input" 
                               placeholder="Sayı girin" 
                               min="<?php echo $diff_settings['min']; ?>" 
                               max="<?php echo $diff_settings['max']; ?>" 
                               required 
                               autofocus
                               autocomplete="off">
                        <button type="submit" class="guess-btn">
                            Tahmin Et <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>

                    <!-- Attempts Progress Tracker -->
                    <div class="progress-container">
                        <div class="progress-header">
                            <span>Tahmin Hakkı</span>
                            <span><strong><?php echo $attempts_left; ?></strong> / <?php echo $max_attempts; ?></span>
                        </div>
                        <div class="progress-track">
                            <?php 
                                // Progress bar color based on percentage
                                $bar_color = 'var(--success)';
                                if ($progress_pct <= 30) {
                                    $bar_color = 'var(--danger)';
                                } elseif ($progress_pct <= 60) {
                                    $bar_color = 'var(--warning)';
                                }
                            ?>
                            <div class="progress-bar" style="width: <?php echo $progress_pct; ?>%; background-color: <?php echo $bar_color; ?>;"></div>
                        </div>
                    </div>
                </form>

                <!-- Feedback alert messages inside active game -->
                <?php if ($feedback_type === 'error'): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo $feedback; ?></span>
                    </div>
                <?php elseif ($feedback_type === 'higher'): ?>
                    <div class="alert alert-higher">
                        <i class="fa-solid fa-circle-arrow-up"></i>
                        <span><?php echo $feedback; ?></span>
                    </div>
                <?php elseif ($feedback_type === 'lower'): ?>
                    <div class="alert alert-lower">
                        <i class="fa-solid fa-circle-arrow-down"></i>
                        <span><?php echo $feedback; ?></span>
                    </div>
                <?php endif; ?>

            <?php elseif ($game_status === 'won'): ?>
                <!-- WON GAME VIEW -->
                <div class="game-over-card">
                    <div class="result-icon won">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <h2 class="result-title" style="color: var(--success);">Tebrikler!</h2>
                    <p class="result-message">
                        Gizli sayıyı başarıyla buldunuz! <br>
                        Doğru sayı: <strong><?php echo $secret_number; ?></strong> <br>
                        Deneme sayısı: <strong><?php echo ($max_attempts - $attempts_left); ?></strong>
                    </p>
                    
                    <div class="action-buttons">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="action" value="reset">
                            <button type="submit" class="btn btn-primary">
                                Yeniden Başlat <i class="fa-solid fa-rotate-right"></i>
                            </button>
                        </form>
                    </div>
                </div>

            <?php elseif ($game_status === 'lost'): ?>
                <!-- LOST GAME VIEW -->
                <div class="game-over-card">
                    <div class="result-icon lost">
                        <i class="fa-solid fa-heart-crack"></i>
                    </div>
                    <h2 class="result-title" style="color: var(--danger);">Oyun Bitti!</h2>
                    <p class="result-message">
                        Bütün tahmin haklarınızı tükettiniz. <br>
                        Doğru sayı: <strong><?php echo $secret_number; ?></strong>
                    </p>
                    
                    <div class="action-buttons">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="action" value="reset">
                            <button type="submit" class="btn btn-primary">
                                Tekrar Dene <i class="fa-solid fa-rotate-right"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </main>

        <!-- HISTORY PANELS -->
        <?php if (!empty($history)): ?>
            <section class="glass-card history-card">
                <div class="section-title">Tahmin Geçmişi (<?php echo count($history); ?>)</div>
                <ul class="history-list">
                    <?php foreach ($history as $h): ?>
                        <li class="history-item">
                            <span class="history-num">
                                <i class="fa-solid fa-hashtag" style="font-size: 0.8rem; color: var(--text-secondary);"></i>
                                <?php echo htmlspecialchars($h['guess']); ?>
                            </span>
                            <?php 
                                $badge_class = 'badge-correct';
                                if ($h['result'] === 'higher') {
                                    $badge_class = 'badge-higher';
                                } elseif ($h['result'] === 'lower') {
                                    $badge_class = 'badge-lower';
                                }
                            ?>
                            <span class="history-badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars($h['hint']); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <!-- RESET STATS SECTION -->
        <div class="footer-action">
            <form method="POST" action="index.php" onsubmit="return confirm('Tüm skor rekorlarınızı ve serinizi sıfırlamak istediğinize emin misiniz?');">
                <input type="hidden" name="action" value="reset_stats">
                <button type="submit" class="reset-stats-btn">İstatistikleri Sıfırla</button>
            </form>
        </div>

    </div>

    <!-- Canvas Confetti for winning celebration -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <script>
        // Form submissions handling for Difficulty selection
        function changeDifficulty(diff) {
            document.getElementById('selectedDifficulty').value = diff;
            document.getElementById('diffForm').submit();
        }

        // Trigger confetti if game was won
        <?php if ($game_status === 'won'): ?>
        window.addEventListener('load', () => {
            const duration = 3 * 1000;
            const end = Date.now() + duration;

            (function frame() {
                confetti({
                    particleCount: 5,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 },
                    colors: ['#6366f1', '#a5b4fc', '#10b981', '#fbbf24']
                });
                confetti({
                    particleCount: 5,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 },
                    colors: ['#6366f1', '#a5b4fc', '#10b981', '#fbbf24']
                });

                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());
        });
        <?php endif; ?>

        // Simple autofocus fix and styling enhancements
        document.addEventListener("DOMContentLoaded", () => {
            const guessInput = document.getElementById('guessInput');
            if (guessInput) {
                guessInput.focus();
                
                // Clear validation shake after animation runs
                const shaker = document.querySelector('.shake-element');
                if (shaker) {
                    setTimeout(() => {
                        shaker.classList.remove('shake-element');
                    }, 500);
                }
            }
        });
    </script>
</body>
</html>
