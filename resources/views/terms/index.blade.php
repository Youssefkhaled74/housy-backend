<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Privacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --dark-color: #34495e;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }

        .main-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin: 30px auto;
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        .section-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 25px;
            border: 1px solid #eee;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .section-title i {
            color: var(--secondary-color);
            font-size: 1.5rem;
        }

        .policy-item {
            margin-bottom: 20px;
        }

        .policy-item h5 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .highlight-box {
            background-color: #f8f9fa;
            border-left: 4px solid var(--secondary-color);
            padding: 20px;
            margin: 25px 0;
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            color: var(--dark-color);
            font-weight: 600;
            border: none;
            padding: 12px 25px;
        }

        .nav-tabs .nav-link.active {
            color: var(--secondary-color);
            background: transparent;
            border-bottom: 3px solid var(--secondary-color);
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 15px;
                border-radius: 8px;
            }
            
            .section-card {
                padding: 20px;
            }
            
            .header-section {
                padding: 30px 0;
            }
        }

        ol, ul {
            padding-left: 20px;
        }

        li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header Section -->
            <div class="header-section">
                <div class="header-content">
                    <h1 class="display-4 mb-3">
                        <i class="fas fa-file-contract me-3"></i>
                        Terms & Privacy Policy
                    </h1>
                </div>
            </div>

            <div class="container py-4">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs mb-4" id="policyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab">Terms of Service</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab">Privacy Policy</button>
                    </li>
                </ul>

                <div class="tab-content" id="policyTabsContent">
                    <!-- Terms of Service Tab -->
                    <div class="tab-pane fade show active" id="terms" role="tabpanel">
                        <div class="section-card">
                            <h2 class="section-title">
                                <i class="fas fa-gavel"></i>
                                Terms of Service
                            </h2>
                            <p>Welcome to our e-commerce platform. By accessing or using our service, you agree to be bound by these terms.</p>

                            <div class="policy-item">
                                <h5><i class="fas fa-shopping-cart me-2"></i> Account Registration</h5>
                                <p>You must create an account to make purchases. You are responsible for maintaining the confidentiality of your account credentials.</p>
                            </div>

                            <div class="policy-item">
                                <h5><i class="fas fa-ban me-2"></i> Prohibited Activities</h5>
                                <p>You agree not to:</p>
                                <ul>
                                    <li>Use the service for any illegal purpose</li>
                                    <li>Attempt to gain unauthorized access to our systems</li>
                                    <li>Interfere with the proper working of the service</li>
                                    <li>Use any automated system to access the service</li>
                                </ul>
                            </div>

                            <div class="policy-item">
                                <h5><i class="fas fa-exchange-alt me-2"></i> Returns & Refunds</h5>
                                <p>We accept returns within 30 days of purchase. Items must be in original condition with all tags attached. Refunds will be processed within 14 business days.</p>
                            </div>

                            <div class="highlight-box">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i> Important Notice</h5>
                                <p>We reserve the right to modify these terms at any time. Your continued use of the service constitutes acceptance of any changes.</p>
                            </div>
                        </div>

                        <div class="section-card">
                            <h2 class="section-title">
                                <i class="fas fa-credit-card"></i>
                                Payment Terms
                            </h2>
                            <div class="policy-item">
                                <h5>Accepted Payment Methods</h5>
                                <p>We accept all major credit cards, PayPal, and Apple Pay. All transactions are processed securely.</p>
                            </div>
                            <div class="policy-item">
                                <h5>Sales Tax</h5>
                                <p>Applicable sales tax will be added to your order based on your shipping address.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Policy Tab -->
                    <div class="tab-pane fade" id="privacy" role="tabpanel">
                        <div class="section-card">
                            <h2 class="section-title">
                                <i class="fas fa-user-shield"></i>
                                Privacy Policy
                            </h2>
                            <p>We are committed to protecting your privacy. This policy explains how we collect, use, and protect your information.</p>

                            <div class="policy-item">
                                <h5><i class="fas fa-database me-2"></i> Information We Collect</h5>
                                <p>We may collect:</p>
                                <ul>
                                    <li>Personal information (name, email, address) when you register</li>
                                    <li>Payment information to process transactions</li>
                                    <li>Usage data to improve our services</li>
                                    <li>Cookies to enhance your browsing experience</li>
                                </ul>
                            </div>

                            <div class="policy-item">
                                <h5><i class="fas fa-chart-line me-2"></i> How We Use Your Information</h5>
                                <p>Your information is used to:</p>
                                <ol>
                                    <li>Process transactions and deliver products</li>
                                    <li>Improve our website and customer service</li>
                                    <li>Send periodic emails (you can unsubscribe at any time)</li>
                                    <li>Prevent fraud and enhance security</li>
                                </ol>
                            </div>

                            <div class="highlight-box">
                                <h5><i class="fas fa-lock me-2"></i> Data Protection</h5>
                                <p>We implement security measures to maintain the safety of your personal information. All sensitive/credit information is transmitted via SSL technology.</p>
                            </div>

                            <div class="policy-item">
                                <h5><i class="fas fa-cookie-bite me-2"></i> Cookies</h5>
                                <p>We use cookies to understand and save your preferences for future visits and compile aggregate data about site traffic.</p>
                            </div>

                            <div class="policy-item">
                                <h5><i class="fas fa-share-square me-2"></i> Third-Party Disclosure</h5>
                                <p>We do not sell, trade, or otherwise transfer your personally identifiable information to outside parties except trusted third parties who assist us in operating our website.</p>
                            </div>
                        </div>

                        <div class="section-card">
                            <h2 class="section-title">
                                <i class="fas fa-phone"></i>
                                Contact Us
                            </h2>
                            <p>If you have any questions about these policies, please contact us at:</p>
                            <ul>
                                <li>Email: privacy@yourecommerce.com</li>
                                <li>Phone: 1-800-123-4567</li>
                                <li>Mail: 123 Commerce St, Business City, BC 12345</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activate tab switching
        document.addEventListener('DOMContentLoaded', function() {
            var policyTabs = new bootstrap.Tab(document.getElementById('terms-tab'));
            policyTabs.show();
        });
    </script>
</body>
</html>