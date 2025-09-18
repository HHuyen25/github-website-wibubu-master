<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Không có quyền truy cập - Wibubu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .unauthorized-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-error-100), var(--color-warning-100));
            padding: var(--space-xl);
        }
        
        .unauthorized-card {
            background: var(--gradient-glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-3xl);
            padding: var(--space-4xl);
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: var(--shadow-2xl);
        }
        
        .unauthorized-icon {
            font-size: 120px;
            margin-bottom: var(--space-2xl);
        }
        
        .unauthorized-title {
            color: var(--color-error-700);
            font-size: var(--font-size-4xl);
            font-weight: 700;
            margin-bottom: var(--space-lg);
        }
        
        .unauthorized-message {
            color: var(--color-neutral-700);
            font-size: var(--font-size-xl);
            margin-bottom: var(--space-3xl);
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: var(--space-lg);
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-md);
            padding: var(--space-lg) var(--space-2xl);
            border-radius: var(--radius-lg);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-secondary {
            background: var(--gradient-glass);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--color-neutral-700);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
    </style>
</head>
<body>
    <div class="unauthorized-container">
        <div class="unauthorized-card">
            <div class="unauthorized-icon">🚫</div>
            <h1 class="unauthorized-title">Không có quyền truy cập</h1>
            <p class="unauthorized-message">
                Xin lỗi, bạn không có quyền truy cập vào trang này. 
                Vui lòng đăng nhập với tài khoản có quyền phù hợp hoặc liên hệ quản trị viên để được hỗ trợ.
            </p>
            
            <div class="action-buttons">
                <a href="javascript:history.back()" class="action-btn btn-primary">
                    <span>←</span>
                    <span>Quay lại trang trước</span>
                </a>
                
                <a href="index.php" class="action-btn btn-secondary">
                    <span>🏠</span>
                    <span>Về trang chủ</span>
                </a>
                
                <a href="login.php" class="action-btn btn-secondary">
                    <span>🔐</span>
                    <span>Đăng nhập lại</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>