// Admin API Configuration
const ADMIN_CONFIG = {
    // Set to true to use fallback API (works without database)
    USE_FALLBACK_API: false,
    
    // API endpoints
    MAIN_API: '../backend/api/admin/admin-data.php',
    FALLBACK_API: '../backend/api/admin/admin-data-fallback.php',
    
    // Get current API endpoint
    getApiEndpoint: function() {
        return this.USE_FALLBACK_API ? this.FALLBACK_API : this.MAIN_API;
    },
    
    // Test API availability
    testApi: async function() {
        try {
            const token = localStorage.getItem('admin_token');
            if (!token) {
                throw new Error('No admin token found');
            }
            
            const response = await fetch(this.getApiEndpoint() + '?action=stats', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            return { success: true, data };
            
        } catch (error) {
            return { success: false, error: error.message };
        }
    },
    
    // Auto-detect best API
    autoDetect: async function() {
        console.log('🔍 Testing main API...');
        this.USE_FALLBACK_API = false;
        let result = await this.testApi();
        
        if (!result.success) {
            console.log('❌ Main API failed, switching to fallback API...');
            this.USE_FALLBACK_API = true;
            result = await this.testApi();
            
            if (result.success) {
                console.log('✅ Fallback API working');
                this.showApiNotice('fallback');
            } else {
                console.log('❌ Both APIs failed');
                this.showApiNotice('error');
            }
        } else {
            console.log('✅ Main API working');
            this.showApiNotice('main');
        }
        
        return result.success;
    },
    
    // Show API status notice
    showApiNotice: function(type) {
        const notices = {
            main: {
                message: '✅ متصل به دیتابیس اصلی',
                class: 'bg-green-100 border-green-400 text-green-700'
            },
            fallback: {
                message: '⚠️ در حال استفاده از داده‌های نمونه (دیتابیس در دسترس نیست)',
                class: 'bg-yellow-100 border-yellow-400 text-yellow-700'
            },
            error: {
                message: '❌ خطا در اتصال به API - لطفاً سرور را بررسی کنید',
                class: 'bg-red-100 border-red-400 text-red-700'
            }
        };
        
        const notice = notices[type];
        if (!notice) return;
        
        // Remove existing notices
        const existingNotice = document.getElementById('api-status-notice');
        if (existingNotice) {
            existingNotice.remove();
        }
        
        // Create new notice
        const noticeEl = document.createElement('div');
        noticeEl.id = 'api-status-notice';
        noticeEl.className = `${notice.class} border px-4 py-3 rounded mb-4 text-sm`;
        noticeEl.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${notice.message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="text-lg font-bold">&times;</button>
            </div>
        `;
        
        // Insert at top of main content
        const mainContent = document.querySelector('main') || document.body;
        mainContent.insertBefore(noticeEl, mainContent.firstChild);
    }
};

// Export for use in other scripts
window.ADMIN_CONFIG = ADMIN_CONFIG;
