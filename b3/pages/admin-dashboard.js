let adminToken = localStorage.getItem('admin_token');
let currentSection = 'users';

// API Configuration
const AUTH_API_URL = '../backend/api/auth';

// Get API endpoint from configuration
function getApiEndpoint() {
    return window.ADMIN_CONFIG ? window.ADMIN_CONFIG.getApiEndpoint() : '../backend/api/admin/admin-data.php';
}

// Cache for data
let dataCache = {
    users: null,
    jobs: null,
    requests: null,
    messages: null,
    stats: null,
    lastUpdate: {}
};

// Cache duration (5 minutes)
const CACHE_DURATION = 5 * 60 * 1000;

// Initialize with faster loading
document.addEventListener('DOMContentLoaded', async function() {
    // Show loading indicator
    showGlobalLoading(true);
    
    try {
        // Auto-detect best API if configuration is available
        if (window.ADMIN_CONFIG) {
            await window.ADMIN_CONFIG.autoDetect();
        }
        
        // Load stats and default section in parallel
        await Promise.all([
            loadStats(),
            loadSectionData('users'),
            checkDatabaseConnection(),
            loadNullIdsStats()
        ]);
        
        // Show users section by default
        showSection('users', false); // false = don't reload data
        
        // Start periodic database monitoring
        setInterval(checkDatabaseConnection, 30000); // Check every 30 seconds
        
        // Preload other sections in background
        setTimeout(() => {
            preloadSections();
        }, 1000);
        
    } catch (error) {
        console.error('Dashboard initialization error:', error);
        showErrorMessage('خطا در بارگذاری داشبورد');
    } finally {
        showGlobalLoading(false);
    }
});

// Load statistics
async function loadStats() {
    try {
        const response = await fetch(getApiEndpoint() + '?action=stats', {
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            // Handle both possible response formats
            const stats = data.stats || data;
            document.getElementById('totalUsers').textContent = stats.users || stats.total_users || 0;
            document.getElementById('totalJobs').textContent = stats.jobs || stats.total_jobs || 0;
            document.getElementById('activeProjects').textContent = stats.projects || stats.active_projects || 0;
            document.getElementById('totalRequests').textContent = stats.requests || stats.total_requests || 0;
            document.getElementById('totalMessages').textContent = stats.messages || stats.total_messages || 0;
        } else {
            console.error('Failed to load stats:', response.status);
            setDefaultStats();
        }
    } catch (error) {
        console.error('Error loading stats:', error);
        setDefaultStats();
    }
}

function setDefaultStats() {
    document.getElementById('totalUsers').textContent = '0';
    document.getElementById('totalJobs').textContent = '0';
    document.getElementById('activeProjects').textContent = '0';
    document.getElementById('totalRequests').textContent = '0';
    document.getElementById('totalMessages').textContent = '0';
}

// Performance optimization functions
function isCacheValid(section) {
    const lastUpdate = dataCache.lastUpdate[section];
    return lastUpdate && (Date.now() - lastUpdate) < CACHE_DURATION;
}

function setCacheData(section, data) {
    dataCache[section] = data;
    dataCache.lastUpdate[section] = Date.now();
    
    // Show cache indicator
    showCacheIndicator(section, true);
}

function getCacheData(section) {
    return isCacheValid(section) ? dataCache[section] : null;
}

function showGlobalLoading(show) {
    const loadingEl = document.getElementById('globalLoading');
    if (loadingEl) {
        loadingEl.style.display = show ? 'flex' : 'none';
    }
}

function showErrorMessage(message) {
    // Create or update error message
    let errorEl = document.getElementById('errorMessage');
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.id = 'errorMessage';
        errorEl.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        document.body.appendChild(errorEl);
    }
    errorEl.textContent = message;
    errorEl.style.display = 'block';
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        errorEl.style.display = 'none';
    }, 5000);
}

function preloadSections() {
    const sections = ['jobs', 'projects', 'requests', 'messages'];
    sections.forEach(section => {
        if (!getCacheData(section)) {
            loadSectionData(section, true); // true = silent loading
        }
    });
}

// Enhanced show section with caching
function showSection(section, reload = true) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    
    // Remove active state from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = '';
        btn.style.color = '';
        btn.style.transform = '';
        btn.style.boxShadow = '';
    });
    
    // Show selected section
    const sectionEl = document.getElementById(section + '-section');
    if (sectionEl) {
        sectionEl.classList.add('active');
    }
    
    // Activate selected tab
    const tabBtn = document.querySelector(`[onclick="showSection('${section}')"]`);
    if (tabBtn) {
        tabBtn.classList.add('active');
    }
    
    currentSection = section;
    
    // Load data with caching
    if (reload) {
        const cachedData = getCacheData(section);
        if (cachedData) {
            displaySectionData(section, cachedData);
        } else {
            loadSectionData(section);
        }
    }
}

// Enhanced load section data with caching
async function loadSectionData(section, silent = false) {
    // Show loading indicator for the section
    if (!silent) {
        showSectionLoading(section, true);
    }
    
    const startTime = performance.now();
    
    try {
        const response = await fetch(`${getApiEndpoint()}?action=${section}`, {
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Cache the data
            setCacheData(section, data);
            
            // Display data if this is the current section or not silent
            if (!silent || currentSection === section) {
                displaySectionData(section, data);
            }
        } else {
            console.error(`Failed to load ${section}:`, response.status);
            const emptyData = { [section]: [] };
            if (!silent) {
                displaySectionData(section, emptyData);
                showErrorMessage(`خطا در بارگذاری ${getSectionTitle(section)}`);
            }
        }
    } catch (error) {
        console.error(`Error loading ${section}:`, error);
        const emptyData = { [section]: [] };
        if (!silent) {
            displaySectionData(section, emptyData);
            showErrorMessage(`خطا در اتصال به سرور`);
        }
    } finally {
        if (!silent) {
            showSectionLoading(section, false);
        }
    }
}

// Helper functions for better UX
function showSectionLoading(section, show) {
    const container = document.getElementById(section + 'List');
    if (container) {
        if (show) {
            container.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="mr-3 text-gray-600">در حال بارگذاری...</span>
                </div>
            `;
        }
    }
}

// Format budget display
function formatBudget(budgetType, budgetMin, budgetMax) {
    if (!budgetType) return 'نامشخص';
    
    switch (budgetType) {
        case 'fixed':
            return budgetMin ? `${formatPrice(budgetMin)} تومان` : 'نامشخص';
        case 'range':
            if (budgetMin && budgetMax) {
                return `${formatPrice(budgetMin)} - ${formatPrice(budgetMax)} تومان`;
            } else if (budgetMin) {
                return `${formatPrice(budgetMin)} تومان`;
            }
            return 'نامشخص';
        case 'negotiable':
            return 'قابل مذاکره';
        default:
            return 'نامشخص';
    }
}

// Get budget type text
function getBudgetTypeText(budgetType) {
    switch (budgetType) {
        case 'fixed':
            return 'مبلغ ثابت';
        case 'range':
            return 'محدوده قیمت';
        case 'negotiable':
            return 'قابل مذاکره';
        default:
            return 'نامشخص';
    }
}

// View job details function
function viewJobDetails(jobId) {
    showJobDetailsModal(jobId);
}

// Show job details modal
async function showJobDetailsModal(jobId) {
    try {
        const response = await fetch(`${getApiEndpoint()}?action=job&id=${jobId}`, {
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            const job = await response.json();
            displayJobDetails(job);
            
            const modal = document.getElementById('jobDetailsModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            alert('❌ خطا در بارگذاری جزئیات آگهی');
        }
    } catch (error) {
        console.error('Error loading job details:', error);
        alert('❌ خطا در اتصال به سرور');
    }
}

// Close job details modal
function closeJobDetailsModal() {
    const modal = document.getElementById('jobDetailsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Display job details in modal
function displayJobDetails(job) {
    const content = document.getElementById('jobDetailsContent');
    const title = document.getElementById('jobDetailsModalTitle');
    
    title.textContent = `جزئیات آگهی - ${job.title}`;
    
    content.innerHTML = `
        <div class="space-y-6">
            <!-- Job Header -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-briefcase text-white text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">${job.title}</h2>
                            <p class="text-gray-600">شناسه: ${job.job_id}</p>
                        </div>
                    </div>
                    <div class="text-left">
                        <div class="text-sm text-gray-500">تاریخ ایجاد</div>
                        <div class="font-medium">${formatDate(job.created_at)}</div>
                    </div>
                </div>
            </div>
            
            <!-- Job Description -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-align-right ml-2 text-blue-600"></i>
                    توضیحات آگهی
                </h3>
                <div class="text-gray-700 leading-relaxed whitespace-pre-wrap">
                    ${job.description || 'بدون توضیحات'}
                </div>
            </div>
            
            <!-- Job Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Budget Information -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-money-bill-wave ml-2 text-green-600"></i>
                        اطلاعات بودجه
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">نوع بودجه:</span>
                            <span class="font-medium">${getBudgetTypeText(job.budget_type)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">مبلغ:</span>
                            <span class="font-medium">${formatBudget(job.budget_type, job.budget_min, job.budget_max)}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Employer Information -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-user-tie ml-2 text-purple-600"></i>
                        اطلاعات کارفرما
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">نام:</span>
                            <span class="font-medium">${job.first_name} ${job.last_name}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">شرکت:</span>
                            <span class="font-medium">${job.company_name || 'بدون شرکت'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ایمیل:</span>
                            <span class="font-medium">${job.email || 'نامشخص'}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 space-x-reverse pt-6 border-t border-gray-200">
                <button onclick="closeJobDetailsModal()" 
                        class="px-6 py-3 text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-all">
                    بستن
                </button>
                <button onclick="deleteJob(${job.job_id})" 
                        class="px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all font-medium">
                    <i class="fas fa-trash ml-2"></i>
                    حذف آگهی
                </button>
            </div>
        </div>
    `;
}

function getSectionTitle(section) {
    const titles = {
        'users': 'کاربران',
        'jobs': 'آگهی‌ها',
        'projects': 'پروژه‌ها',
        'requests': 'درخواست‌ها',
        'messages': 'پیام‌ها'
    };
    return titles[section] || section;
}

// Refresh data function
function showCacheIndicator(section, show) {
    const indicator = document.getElementById(section + 'Cache');
    if (indicator) {
        if (show) {
            indicator.classList.remove('hidden');
        } else {
            indicator.classList.add('hidden');
        }
    }
}

function refreshSection(section = currentSection) {
    // Clear cache for this section
    delete dataCache[section];
    delete dataCache.lastUpdate[section];
    
    // Hide cache indicator
    showCacheIndicator(section, false);
    
    // Reload data
    loadSectionData(section);
}

// Refresh all data
function refreshAllData() {
    // Show global loading
    showGlobalLoading(true);
    
    // Clear all cache
    dataCache = {
        users: null,
        jobs: null,
        projects: null,
        requests: null,
        messages: null,
        stats: null,
        lastUpdate: {}
    };
    
    // Hide all cache indicators
    ['users', 'jobs', 'projects', 'requests', 'messages'].forEach(section => {
        showCacheIndicator(section, false);
    });
    
    // Reload current section and stats in parallel
    Promise.all([
        loadStats(),
        loadSectionData(currentSection)
    ]).finally(() => {
        showGlobalLoading(false);
    });
}

// Keyboard shortcuts for better UX
document.addEventListener('keydown', function(e) {
    // Only activate shortcuts when not typing in inputs
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    // Ctrl/Cmd + R: Refresh current section
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        refreshSection();
        return;
    }
    
    // Ctrl/Cmd + Shift + R: Refresh all data
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'R') {
        e.preventDefault();
        refreshAllData();
        return;
    }
    
    // Number keys 1-5 for quick section switching
    const sectionKeys = {
        '1': 'users',
        '2': 'jobs', 
        '3': 'projects',
        '4': 'requests',
        '5': 'messages'
    };
    
    if (sectionKeys[e.key]) {
        e.preventDefault();
        showSection(sectionKeys[e.key]);
    }
});

// Display section data
function displaySectionData(section, data) {
    const container = document.getElementById(section + 'Table');
    
    if (!container) {
        console.error(`Container not found for section: ${section}`);
        return;
    }
    
    let html = '';
    
    switch(section) {
        case 'users':
            if (data.users && data.users.length > 0) {
                html = data.users.map(user => `
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${user.user_id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${user.first_name} ${user.last_name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full ${getUserTypeClass(user.user_type)}">
                                ${getUserTypeText(user.user_type)}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.company_name || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(user.created_at)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2 space-x-reverse">
                                <button onclick="editUser(${user.user_id})" class="text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-50 transition-colors" title="ویرایش">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteUser(${user.user_id})" class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                html = `
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                <p class="text-lg font-medium">هیچ کاربری یافت نشد</p>
                                <p class="text-sm">کاربران جدید به‌زودی اضافه خواهند شد</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
            break;
            
        case 'jobs':
            if (data.jobs && data.jobs.length > 0) {
                html = data.jobs.map(job => `
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${job.job_id}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="max-w-xs">
                                <div class="font-medium text-gray-900 truncate">${job.title || '-'}</div>
                                <div class="text-xs text-gray-500 mt-1 line-clamp-2">${(job.description || '').substring(0, 100)}${job.description && job.description.length > 100 ? '...' : ''}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-6 w-6">
                                    <div class="h-6 w-6 rounded-full bg-gradient-to-r from-green-400 to-blue-500 flex items-center justify-center text-white text-xs font-bold">
                                        ${(job.first_name?.charAt(0) || 'E').toUpperCase()}
                                    </div>
                                </div>
                                <div class="mr-2">
                                    <div class="text-sm font-medium text-gray-900">${job.first_name || ''} ${job.last_name || ''}</div>
                                    ${job.company_name ? `<div class="text-xs text-gray-500">${job.company_name}</div>` : ''}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center">
                                <i class="fas fa-coins text-yellow-500 ml-1"></i>
                                ${job.budget || 'قابل مذاکره'}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-alt text-gray-400 ml-1"></i>
                                ${formatDate(job.created_at)}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2 space-x-reverse">
                                <button onclick="editJob(${job.job_id})" class="text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-50 transition-colors" title="ویرایش">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteJob(${job.job_id})" class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                html = `
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-briefcase text-4xl text-gray-300 mb-3"></i>
                                <p class="text-lg font-medium">هیچ آگهی شغلی یافت نشد</p>
                                <p class="text-sm">آگهی‌های جدید به‌زودی اضافه خواهند شد</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
            break;
            
        case 'requests':
            if (data.requests && data.requests.length > 0) {
                html = data.requests.map(request => `
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${request.request_id}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium">${request.job_title || '-'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <div class="h-6 w-6 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs mr-2">
                                    ${(request.employer_first_name?.charAt(0) || 'E').toUpperCase()}
                                </div>
                                ${request.employer_first_name || ''} ${request.employer_last_name || ''}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <div class="h-6 w-6 rounded-full bg-green-500 flex items-center justify-center text-white text-xs mr-2">
                                    ${(request.freelancer_first_name?.charAt(0) || 'F').toUpperCase()}
                                </div>
                                ${request.freelancer_first_name || ''} ${request.freelancer_last_name || ''}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full ${getRequestStatusClass(request.status)}">
                                ${getRequestStatusText(request.status)}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2 space-x-reverse">
                                <button onclick="viewRequest(${request.request_id})" class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50 transition-colors" title="مشاهده">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="deleteRequest(${request.request_id})" class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                html = `
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-handshake text-4xl text-gray-300 mb-3"></i>
                                <p class="text-lg font-medium">هیچ درخواستی یافت نشد</p>
                                <p class="text-sm">درخواست‌های جدید به‌زودی اضافه خواهند شد</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
            break;
            
        case 'messages':
            if (data.messages && data.messages.length > 0) {
                html = data.messages.map(message => `
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${message.message_id}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="max-w-xs">
                                <div class="font-medium text-gray-900 truncate">${message.title || '-'}</div>
                                <div class="text-xs text-gray-500 mt-1 truncate">${(message.message || '').substring(0, 50)}${message.message && message.message.length > 50 ? '...' : ''}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <div class="h-6 w-6 rounded-full bg-purple-500 flex items-center justify-center text-white text-xs mr-2">
                                    ${(message.sender_first_name?.charAt(0) || 'S').toUpperCase()}
                                </div>
                                ${message.sender_first_name || ''} ${message.sender_last_name || ''}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <div class="h-6 w-6 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs mr-2">
                                    ${(message.receiver_first_name?.charAt(0) || 'R').toUpperCase()}
                                </div>
                                ${message.receiver_first_name || ''} ${message.receiver_last_name || ''}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full ${
                                message.is_read ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                            }">
                                ${message.is_read ? 'خوانده شده' : 'خوانده نشده'}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-gray-400 ml-1"></i>
                                ${formatDate(message.created_at)}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2 space-x-reverse">
                                <button onclick="viewMessage(${message.message_id})" class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50 transition-colors" title="مشاهده">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="deleteMessage(${message.message_id})" class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                html = `
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-envelope text-4xl text-gray-300 mb-3"></i>
                                <p class="text-lg font-medium">هیچ پیامی یافت نشد</p>
                                <p class="text-sm">پیام‌های جدید به‌زودی اضافه خواهند شد</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
            break;
    }
    
    // Use innerHTML for better performance with large datasets
    container.innerHTML = html;
}

// Helper functions for better UX and performance
function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fa-IR', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch {
        return '-';
    }
}

function formatPrice(price) {
    if (!price) return '-';
    return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
}

function getRequestStatusClass(status) {
    const statusClasses = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'accepted': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'ongoing': 'bg-blue-100 text-blue-800',
        'completed': 'bg-purple-100 text-purple-800'
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
}

function getRequestStatusText(status) {
    const statusTexts = {
        'pending': 'در انتظار',
        'accepted': 'پذیرفته شده',
        'rejected': 'رد شده',
        'ongoing': 'در حال انجام',
        'completed': 'تکمیل شده'
    };
    return statusTexts[status] || status;
}

function getJobStatusClass(status) {
    const statusClasses = {
        'active': 'bg-green-100 text-green-800',
        'closed': 'bg-red-100 text-red-800',
        'completed': 'bg-purple-100 text-purple-800'
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
}

function getJobStatusText(status) {
    const statusTexts = {
        'active': 'فعال',
        'closed': 'بسته شده',
        'completed': 'تکمیل شده'
    };
    return statusTexts[status] || status;
}

function getProjectStatusClass(status) {
    switch(status) {
        case 'ongoing': return 'bg-yellow-100 text-yellow-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getProjectStatusText(status) {
    switch(status) {
        case 'ongoing': return 'در حال انجام';
        case 'completed': return 'تکمیل شده';
        case 'delivered': return 'تحویل داده شده';
        default: return status;
    }
}

function getRequestStatusClass(status) {
    switch(status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'accepted': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getRequestStatusText(status) {
    switch(status) {
        case 'pending': return 'در انتظار';
        case 'accepted': return 'پذیرفته شده';
        case 'rejected': return 'رد شده';
        default: return status;
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fa-IR');
}

function formatPrice(price) {
    if (!price) return '-';
    return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
}

// CRUD Operations with updated API calls
async function deleteUser(userId) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟\n\nاین عملیات غیرقابل بازگشت است.')) return;
    
    try {
        const response = await fetch(`${getApiEndpoint()}?action=user&id=${userId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            alert('کاربر با موفقیت حذف شد');
            loadSectionData('users');
            loadStats();
        } else {
            const error = await response.json();
            alert(error.message || 'خطا در حذف کاربر');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        alert('خطا در اتصال به سرور');
    }
}

async function deleteJob(jobId) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این آگهی را حذف کنید؟\n\nاین عملیات غیرقابل بازگشت است.')) return;
    
    try {
        const response = await fetch(`${getApiEndpoint()}?action=job&id=${jobId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            alert('آگهی با موفقیت حذف شد');
            loadSectionData('jobs');
            loadStats();
        } else {
            const error = await response.json();
            alert(error.message || 'خطا در حذف آگهی');
        }
    } catch (error) {
        console.error('Error deleting job:', error);
        alert('خطا در اتصال به سرور');
    }
}

async function deleteProject(projectId) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این پروژه را حذف کنید؟')) return;
    
    try {
        const response = await fetch(`${getApiEndpoint()}?action=project&id=${projectId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            alert('پروژه با موفقیت حذف شد');
            loadSectionData('projects');
            loadStats();
        } else {
            alert('خطا در حذف پروژه');
        }
    } catch (error) {
        alert('خطا در اتصال به سرور');
    }
}

async function deleteRequest(requestId) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این درخواست را حذف کنید؟')) return;
    
    try {
        const response = await fetch(`${getApiEndpoint()}?action=request&id=${requestId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            alert('درخواست با موفقیت حذف شد');
            loadSectionData('requests');
            loadStats();
        } else {
            alert('خطا در حذف درخواست');
        }
    } catch (error) {
        alert('خطا در اتصال به سرور');
    }
}

async function deleteMessage(messageId) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این پیام را حذف کنید؟')) return;
    
    try {
        const response = await fetch(`${getApiEndpoint()}?action=message&id=${messageId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            alert('پیام با موفقیت حذف شد');
            loadSectionData('messages');
            loadStats();
        } else {
            alert('خطا در حذف پیام');
        }
    } catch (error) {
        alert('خطا در اتصال به سرور');
    }
}

// Edit functions (disabled)
function editUser(userId) {
    alert('قابلیت ویرایش در حال حاضر فعال نیست');
}

function editJob(jobId) {
    alert('قابلیت ویرایش در حال حاضر فعال نیست');
}

function editProject(projectId) {
    alert('قابلیت ویرایش در حال حاضر فعال نیست');
}

// View message function
function viewMessage(messageId) {
    // Create modal for viewing message details
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">جزئیات پیام</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="messageDetails" class="text-center py-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-600">در حال بارگذاری...</p>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Load message details (placeholder - would connect to API)
    setTimeout(() => {
        const detailsEl = document.getElementById('messageDetails');
        if (detailsEl) {
            detailsEl.innerHTML = `
                <div class="text-right space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شناسه پیام:</label>
                        <p class="text-gray-900">${messageId}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">موضوع:</label>
                        <p class="text-gray-900">پیام سیستمی</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">متن پیام:</label>
                        <div class="bg-gray-50 p-3 rounded border text-gray-800">
                            این یک پیام نمونه است. جزئیات کامل پیام از پایگاه داده بارگذاری خواهد شد.
                        </div>
                    </div>
                </div>
            `;
        }
    }, 500);
}

// View request function
function viewRequest(requestId) {
    // Create modal for viewing request details
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">جزئیات درخواست</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="requestDetails" class="text-center py-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-600">در حال بارگذاری...</p>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Load request details (placeholder - would connect to API)
    setTimeout(() => {
        const detailsEl = document.getElementById('requestDetails');
        if (detailsEl) {
            detailsEl.innerHTML = `
                <div class="text-right space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شناسه درخواست:</label>
                        <p class="text-gray-900">${requestId}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">عنوان آگهی:</label>
                        <p class="text-gray-900">درخواست جدید</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">متن درخواست:</label>
                        <div class="bg-gray-50 p-3 rounded border text-gray-800">
                            این یک درخواست نمونه است. جزئیات کامل درخواست از پایگاه داده بارگذاری خواهد شد.
                        </div>
                    </div>
                </div>
            `;
        }
    }, 500);
}

// Performance monitoring
function logPerformance(action, startTime) {
    const endTime = performance.now();
    const duration = endTime - startTime;
    console.log(`عملکرد ${action}: ${duration.toFixed(2)}ms`);
    
    // Show performance indicator for slow operations
    if (duration > 1000) {
        showErrorMessage(`عملیات ${action} کند بود (${Math.round(duration/1000)}s)`);
    }
}

// Database connection monitoring
function checkDatabaseConnection() {
    return fetch(`${getApiEndpoint()}?action=status`, {
        headers: {
            'Authorization': 'Bearer ' + adminToken
        }
    })
    .then(response => {
        const dbIndicator = document.getElementById('dbIndicator');
        const dbStatus = document.getElementById('dbStatus');
        
        if (response.ok) {
            // Database connected
            if (dbIndicator) {
                dbIndicator.className = 'w-2 h-2 bg-green-400 rounded-full animate-pulse';
            }
            if (dbStatus) {
                dbStatus.title = 'دیتابیس متصل و فعال';
            }
            return true;
        } else {
            // Database connection issues
            if (dbIndicator) {
                dbIndicator.className = 'w-2 h-2 bg-yellow-400 rounded-full animate-pulse';
            }
            if (dbStatus) {
                dbStatus.title = 'مشکل در اتصال دیتابیس';
            }
            return false;
        }
    })
    .catch(error => {
        // Database disconnected
        const dbIndicator = document.getElementById('dbIndicator');
        const dbStatus = document.getElementById('dbStatus');
        
        if (dbIndicator) {
            dbIndicator.className = 'w-2 h-2 bg-red-400 rounded-full';
        }
        if (dbStatus) {
            dbStatus.title = 'دیتابیس قطع شده';
        }
        return false;
    });
}

// Auto-refresh functionality
let autoRefreshInterval;
let dbMonitorInterval;

function toggleAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        clearInterval(dbMonitorInterval);
        autoRefreshInterval = null;
        dbMonitorInterval = null;
        
        // Update button appearance
        const btn = document.getElementById('autoRefreshBtn');
        if (btn) {
            btn.className = 'bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md';
        }
        
        showErrorMessage('به‌روزرسانی خودکار غیرفعال شد');
    } else {
        autoRefreshInterval = setInterval(() => {
            if (document.visibilityState === 'visible') {
                refreshSection();
            }
        }, 30000); // Refresh every 30 seconds
        
        // Start database monitoring
        dbMonitorInterval = setInterval(checkDatabaseConnection, 10000); // Check every 10 seconds
        
        // Update button appearance
        const btn = document.getElementById('autoRefreshBtn');
        if (btn) {
            btn.className = 'bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md';
        }
        
        showErrorMessage('به‌روزرسانی خودکار فعال شد (30 ثانیه)');
    }
}

function displayUsers(container, users) {
    if (users.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3 class="text-lg font-semibold mb-2">هیچ کاربری یافت نشد</h3>
                <p class="text-sm">برای شروع، یک کاربر جدید اضافه کنید</p>
            </div>
        `;
        return;
    }
    
    container.className = 'cards-grid';
    container.innerHTML = users.map(user => `
        <div class="data-card">
            <div class="card-header">
                <h3 class="card-title">${user.first_name} ${user.last_name}</h3>
                <span class="card-id">#${user.user_id}</span>
            </div>
            <div class="card-body">
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">ایمیل</div>
                        <div class="field-value">${user.email}</div>
                    </div>
                </div>
                <div class="card-field">
                    <div class="field-icon user-type-${user.user_type}">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">نوع کاربر</div>
                        <div class="field-value">${getUserTypeText(user.user_type)}</div>
                    </div>
                </div>
                ${user.company_name ? `
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">شرکت</div>
                        <div class="field-value">${user.company_name}</div>
                    </div>
                </div>
                ` : ''}
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">تاریخ عضویت</div>
                        <div class="field-value">${formatDate(user.created_at)}</div>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button onclick="editUser(${user.user_id})" class="action-btn btn-edit">
                    <i class="fas fa-edit"></i> ویرایش
                </button>
                <button onclick="deleteUser(${user.user_id})" class="action-btn btn-delete">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
    `).join('');
}

function displayJobs(container, jobs) {
    if (jobs.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-briefcase"></i>
                <h3 class="text-lg font-semibold mb-2">هیچ آگهی‌ای یافت نشد</h3>
                <p class="text-sm">برای شروع، یک آگهی جدید اضافه کنید</p>
            </div>
        `;
        return;
    }
    
    container.className = 'cards-grid';
    container.innerHTML = jobs.map(job => `
        <div class="data-card">
            <div class="card-header">
                <h3 class="card-title">${job.title}</h3>
                <span class="card-id">#${job.job_id}</span>
            </div>
            <div class="card-body">
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">کارفرما</div>
                        <div class="field-value">${job.first_name} ${job.last_name}</div>
                        ${job.company_name ? `<div class="text-xs text-gray-500 mt-1">${job.company_name}</div>` : ''}
                    </div>
                </div>
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">بودجه</div>
                        <div class="field-value">${formatBudget(job.budget_type, job.budget_min, job.budget_max)}</div>
                        <div class="text-xs text-gray-500 mt-1">${getBudgetTypeText(job.budget_type)}</div>
                    </div>
                </div>
                ${job.description ? `
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">توضیحات</div>
                        <div class="field-value text-sm leading-relaxed">${job.description.length > 120 ? job.description.substring(0, 120) + '...' : job.description}</div>
                    </div>
                </div>
                ` : ''}
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">تاریخ ایجاد</div>
                        <div class="field-value">${formatDate(job.created_at)}</div>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button onclick="viewJobDetails(${job.job_id})" class="action-btn btn-view">
                    <i class="fas fa-eye"></i> مشاهده
                </button>
                <button onclick="deleteJob(${job.job_id})" class="action-btn btn-delete">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
    `).join('');
}



function displayRequests(container, requests) {
    if (requests.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-handshake"></i>
                <h3 class="text-lg font-semibold mb-2">هیچ درخواستی یافت نشد</h3>
                <p class="text-sm">درخواست‌های همکاری در اینجا نمایش داده می‌شوند</p>
            </div>
        `;
        return;
    }
    
    container.className = 'cards-grid';
    container.innerHTML = requests.map(request => `
        <div class="data-card">
            <div class="card-header">
                <h3 class="card-title">${request.job_title}</h3>
                <span class="card-id">#${request.request_id}</span>
            </div>
            <div class="card-body">
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">کارفرما</div>
                        <div class="field-value">${request.employer_first_name} ${request.employer_last_name}</div>
                    </div>
                </div>
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">فریلنسر</div>
                        <div class="field-value">${request.freelancer_first_name} ${request.freelancer_last_name}</div>
                    </div>
                </div>
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">قیمت پیشنهادی</div>
                        <div class="field-value">${formatPrice(request.proposed_price)}</div>
                    </div>
                </div>
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">وضعیت</div>
                        <div class="field-value">
                            <span class="status-badge status-${request.status}">${getRequestStatusText(request.status)}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button onclick="viewRequest(${request.request_id})" class="action-btn btn-view">
                    <i class="fas fa-eye"></i> مشاهده
                </button>
                <button onclick="deleteRequest(${request.request_id})" class="action-btn btn-delete">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
    `).join('');
}

function displayMessages(container, messages) {
    if (messages.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-envelope"></i>
                <h3 class="text-lg font-semibold mb-2">هیچ پیامی یافت نشد</h3>
                <p class="text-sm">پیام‌های سیستم در اینجا نمایش داده می‌شوند</p>
            </div>
        `;
        return;
    }
    
    container.className = 'cards-grid';
    container.innerHTML = messages.map(message => `
        <div class="data-card">
            <div class="card-header">
                <h3 class="card-title">${message.subject}</h3>
                <span class="card-id">#${message.message_id}</span>
            </div>
            <div class="card-body">
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">فرستنده</div>
                        <div class="field-value">${message.sender_first_name} ${message.sender_last_name}</div>
                    </div>
                </div>
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">گیرنده</div>
                        <div class="field-value">${message.receiver_first_name} ${message.receiver_last_name}</div>
                    </div>
                </div>
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">تاریخ ارسال</div>
                        <div class="field-value">${formatDate(message.created_at)}</div>
                    </div>
                </div>
                ${message.is_read !== undefined ? `
                <div class="card-field">
                    <div class="field-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="field-content">
                        <div class="field-label">وضعیت</div>
                        <div class="field-value">
                            <span class="status-badge status-${message.is_read ? 'read' : 'unread'}">
                                ${message.is_read ? 'خوانده شده' : 'خوانده نشده'}
                            </span>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
            <div class="card-actions">
                <button onclick="viewMessage(${message.message_id})" class="action-btn btn-view">
                    <i class="fas fa-eye"></i> مشاهده
                </button>
                <button onclick="deleteMessage(${message.message_id})" class="action-btn btn-delete">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
    `).join('');
}

// Helper functions
function getUserTypeClass(type) {
    switch(type) {
        case 'employer': return 'bg-blue-100 text-blue-800';
        case 'job_seeker': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getUserTypeText(type) {
    switch(type) {
        case 'employer': return 'کارفرما';
        case 'job_seeker': return 'فریلنسر';
        default: return type;
    }
}

function getJobStatusClass(status) {
    switch(status) {
        case 'open': return 'bg-green-100 text-green-800';
        case 'in_progress': return 'bg-yellow-100 text-yellow-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        case 'closed': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getJobStatusText(status) {
    switch(status) {
        case 'open': return 'باز';
        case 'in_progress': return 'در حال انجام';
        case 'completed': return 'تکمیل شده';
        case 'closed': return 'بسته';
        default: return status;
    }
}

function getProjectStatusClass(status) {
    switch(status) {
        case 'ongoing': return 'bg-yellow-100 text-yellow-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getProjectStatusText(status) {
    switch(status) {
        case 'ongoing': return 'در حال انجام';
        case 'completed': return 'تکمیل شده';
        case 'delivered': return 'تحویل داده شده';
        default: return status;
    }
}

function getRequestStatusClass(status) {
    switch(status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'accepted': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getRequestStatusText(status) {
    switch(status) {
        case 'pending': return 'در انتظار';
        case 'accepted': return 'پذیرفته شده';
        case 'rejected': return 'رد شده';
        default: return status;
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fa-IR');
}

function formatPrice(price) {
    if (!price) return '-';
    return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
}

// Logout function
async function logout() {
    try {
        const response = await fetch(`${AUTH_API_URL}/admin-logout.php`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + adminToken,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            localStorage.removeItem('admin_token');
            window.location.href = '../admin.php';
        } else {
            alert('خطا در خروج از سیستم');
        }
    } catch (error) {
        console.error('Logout error:', error);
        // Force logout even if API fails
        localStorage.removeItem('admin_token');
        window.location.href = '../admin.php';
    }
}

// ===== Delete Functions for Users and Jobs =====

// Delete user function
async function deleteUser(userId) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟\n\nاین عملیات غیرقابل بازگشت است.\n\nآیدی کاربر حفظ خواهد شد و برای ثبت‌های جدید بازاستفاده می‌شود.')) return;
    
    try {
        const response = await fetch(`${getApiEndpoint()}?action=user&id=${userId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            alert('کاربر با موفقیت حذف شد\n\nآیدی کاربر حفظ شده و برای ثبت‌های جدید بازاستفاده خواهد شد.');
            loadSectionData('users');
            loadStats();
            // Update null IDs stats
            loadNullIdsStats();
        } else {
            const error = await response.json();
            alert(error.message || 'خطا در حذف کاربر');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        alert('خطا در اتصال به سرور');
    }
}

// Delete job function with enhanced UI
async function deleteJob(jobId, buttonElement = null) {
    // Enhanced confirmation dialog
    const confirmed = confirm(
        '🗑️ حذف آگهی\n\n' +
        'آیا مطمئن هستید که می‌خواهید این آگهی را حذف کنید؟\n\n' +
        '⚠️ این عملیات غیرقابل بازگشت است\n\n' +
        '🔄 آیدی آگهی حفظ خواهد شد و برای ثبت‌های جدید بازاستفاده می‌شود'
    );
    
    if (!confirmed) return;
    
    // Try to get button element from event or parameter
    let deleteButton = buttonElement;
    if (!deleteButton && typeof event !== 'undefined' && event.target) {
        deleteButton = event.target;
    }
    
    let originalText = '';
    
    try {
        // Show loading state if button is available
        if (deleteButton) {
            originalText = deleteButton.innerHTML;
            deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i>در حال حذف...';
            deleteButton.disabled = true;
        }
        
        console.log('Deleting job with ID:', jobId);
        console.log('API Endpoint:', getApiEndpoint());
        
        const response = await fetch(`${getApiEndpoint()}?action=job&id=${jobId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + adminToken,
                'Content-Type': 'application/json'
            }
        });
        
        console.log('Delete response status:', response.status);
        
        if (response.ok) {
            const result = await response.json();
            console.log('Delete result:', result);
            
            // Success message with emoji
            alert('✅ آگهی با موفقیت حذف شد!\n\n🔄 آیدی آگهی حفظ شده و برای ثبت‌های جدید بازاستفاده خواهد شد.');
            
            // Refresh data - try enhanced search first, then fallback
            if (typeof performEnhancedJobSearch === 'function') {
                performEnhancedJobSearch();
            } else {
                loadSectionData('jobs');
            }
            loadStats();
            loadNullIdsStats();
        } else {
            let errorMessage = 'خطای نامشخص';
            try {
                const error = await response.json();
                errorMessage = error.message || error.error || 'خطای نامشخص';
            } catch (e) {
                console.error('Error parsing error response:', e);
                errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            }
            console.error('Delete failed:', errorMessage);
            alert('❌ خطا در حذف آگهی\n\n' + errorMessage);
        }
    } catch (error) {
        console.error('Error deleting job:', error);
        alert('❌ خطا در اتصال به سرور\n\nلطفاً اتصال اینترنت خود را بررسی کنید.');
    } finally {
        // Restore button state
        if (deleteButton && originalText) {
            deleteButton.innerHTML = originalText;
            deleteButton.disabled = false;
        }
    }
}

// Load null IDs statistics
async function loadNullIdsStats() {
    try {
        const response = await fetch(`${getApiEndpoint()}?action=null-ids`, {
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            console.log('Null IDs Statistics:', data);
            
            // Calculate total reusable IDs
            const totalReusableIds = data.null_user_ids.total + data.null_job_ids.total;
            
            // Update dashboard display
            const reusableIdsElement = document.getElementById('reusableIds');
            if (reusableIdsElement) {
                reusableIdsElement.textContent = totalReusableIds;
                
                // Add tooltip with detailed info
                reusableIdsElement.title = `کاربران: ${data.null_user_ids.total} | آگهی‌ها: ${data.null_job_ids.total}`;
                
                // Add click event to show details
                reusableIdsElement.parentElement.parentElement.onclick = () => showReusableIdsDetails(data);
            }
            
            // Display in console for admin monitoring
            if (totalReusableIds > 0) {
                console.log('📊 آمار آیدی‌های بازاستفاده شده:');
                console.log(`👥 کاربران حذف شده: ${data.null_user_ids.total} عدد`);
                console.log(`📋 آگهی‌های حذف شده: ${data.null_job_ids.total} عدد`);
                console.log(`🔄 کل آیدی‌های قابل بازاستفاده: ${totalReusableIds} عدد`);
            }
        }
    } catch (error) {
        console.error('Error loading null IDs stats:', error);
    }
}

// Show detailed information about reusable IDs
function showReusableIdsDetails(data) {
    const totalReusableIds = data.null_user_ids.total + data.null_job_ids.total;
    
    if (totalReusableIds === 0) {
        alert('هیچ آیدی بازاستفاده شده‌ای وجود ندارد.');
        return;
    }
    
    let details = '📊 جزئیات آیدی‌های بازاستفاده شده:\n\n';
    details += `👥 کاربران حذف شده: ${data.null_user_ids.total} عدد\n`;
    details += `📋 آگهی‌های حذف شده: ${data.null_job_ids.total} عدد\n`;
    details += `🔄 کل آیدی‌های قابل بازاستفاده: ${totalReusableIds} عدد\n\n`;
    details += '💡 این آیدی‌ها در ثبت‌های جدید بازاستفاده خواهند شد.';
    
    alert(details);
}

// ===== Modal Functions for Adding Users and Jobs =====

// Show add user modal
function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'افزودن کاربر جدید';
    document.getElementById('userForm').reset();
    
    const modal = document.getElementById('userModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Close user modal
function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Show add job modal
async function showAddJobModal() {
    document.getElementById('jobModalTitle').textContent = 'افزودن آگهی جدید';
    document.getElementById('jobForm').reset();
    
    // Load employers for dropdown
    await loadEmployers();
    
    const modal = document.getElementById('jobModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Close job modal
function closeJobModal() {
    const modal = document.getElementById('jobModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Load employers for dropdown
async function loadEmployers() {
    try {
        const response = await fetch(`${getApiEndpoint()}?action=users`, {
            headers: {
                'Authorization': 'Bearer ' + adminToken
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            const employers = data.users.filter(user => user.user_type === 'employer');
            
            const select = document.getElementById('jobUserId');
            select.innerHTML = '<option value="">انتخاب کارفرما</option>';
            
            employers.forEach(employer => {
                const option = document.createElement('option');
                option.value = employer.user_id;
                option.textContent = `${employer.first_name} ${employer.last_name}${employer.company_name ? ` (${employer.company_name})` : ''}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading employers:', error);
    }
}

// Toggle budget fields based on budget type
function toggleBudgetFields(budgetType) {
    const minField = document.getElementById('budgetMinField');
    const maxField = document.getElementById('budgetMaxField');
    
    if (budgetType === 'range') {
        minField.classList.remove('hidden');
        maxField.classList.remove('hidden');
    } else if (budgetType === 'fixed') {
        minField.classList.remove('hidden');
        maxField.classList.add('hidden');
    } else {
        minField.classList.add('hidden');
        maxField.classList.add('hidden');
    }
}

// Handle user form submission
document.addEventListener('DOMContentLoaded', function() {
    // Add search and filter functionality
    setupSearchAndFilters();
    setupEnhancedUserSearch();
    setupEnhancedJobSearch();
    
    function setupSearchAndFilters() {
        // User search and filter
        const userSearch = document.getElementById('userSearch');
        const userTypeFilter = document.getElementById('userTypeFilter');
        if (userSearch) {
            userSearch.addEventListener('input', () => filterData('users'));
        }
        if (userTypeFilter) {
            userTypeFilter.addEventListener('change', () => filterData('users'));
        }
        
        // Job search and filter
        const jobSearch = document.getElementById('jobSearch');
        const jobStatusFilter = document.getElementById('jobStatusFilter');
        if (jobSearch) {
            jobSearch.addEventListener('input', () => filterData('jobs'));
        }
        if (jobStatusFilter) {
            jobStatusFilter.addEventListener('change', () => filterData('jobs'));
        }
        
        // Request search and filter
        const requestSearch = document.getElementById('requestSearch');
        const requestStatusFilter = document.getElementById('requestStatusFilter');
        if (requestSearch) {
            requestSearch.addEventListener('input', () => filterData('requests'));
        }
        if (requestStatusFilter) {
            requestStatusFilter.addEventListener('change', () => filterData('requests'));
        }
        
        // Message search and filter
        const messageSearch = document.getElementById('messageSearch');
        const messageStatusFilter = document.getElementById('messageStatusFilter');
        if (messageSearch) {
            messageSearch.addEventListener('input', () => filterData('messages'));
        }
        if (messageStatusFilter) {
            messageStatusFilter.addEventListener('change', () => filterData('messages'));
        }
    }
    
    // Filter data function
    window.filterData = function(section) {
        const cachedData = getCacheData(section);
        if (!cachedData) return;
        
        let filteredData = [...cachedData];
        
        if (section === 'users') {
            const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
            const typeFilter = document.getElementById('userTypeFilter')?.value || '';
            
            filteredData = filteredData.filter(user => {
                const matchesSearch = !searchTerm || 
                    user.first_name?.toLowerCase().includes(searchTerm) ||
                    user.last_name?.toLowerCase().includes(searchTerm) ||
                    user.email?.toLowerCase().includes(searchTerm) ||
                    user.company_name?.toLowerCase().includes(searchTerm);
                
                const matchesType = !typeFilter || user.user_type === typeFilter;
                
                return matchesSearch && matchesType;
            });
        } else if (section === 'jobs') {
            const searchTerm = document.getElementById('jobSearch')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('jobStatusFilter')?.value || '';
            
            filteredData = filteredData.filter(job => {
                const matchesSearch = !searchTerm || 
                    job.title?.toLowerCase().includes(searchTerm) ||
                    job.description?.toLowerCase().includes(searchTerm) ||
                    (job.first_name + ' ' + job.last_name)?.toLowerCase().includes(searchTerm) ||
                    job.company_name?.toLowerCase().includes(searchTerm);
                
                const matchesStatus = !statusFilter || job.status === statusFilter;
                
                return matchesSearch && matchesStatus;
            });
        } else if (section === 'requests') {
            const searchTerm = document.getElementById('requestSearch')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('requestStatusFilter')?.value || '';
            
            filteredData = filteredData.filter(request => {
                const matchesSearch = !searchTerm || 
                    request.job_title?.toLowerCase().includes(searchTerm) ||
                    (request.employer_first_name + ' ' + request.employer_last_name)?.toLowerCase().includes(searchTerm) ||
                    (request.freelancer_first_name + ' ' + request.freelancer_last_name)?.toLowerCase().includes(searchTerm);
                
                const matchesStatus = !statusFilter || request.status === statusFilter;
                
                return matchesSearch && matchesStatus;
            });
        } else if (section === 'messages') {
            const searchTerm = document.getElementById('messageSearch')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('messageStatusFilter')?.value || '';
            
            filteredData = filteredData.filter(message => {
                const matchesSearch = !searchTerm || 
                    message.subject?.toLowerCase().includes(searchTerm) ||
                    (message.sender_first_name + ' ' + message.sender_last_name)?.toLowerCase().includes(searchTerm) ||
                    (message.receiver_first_name + ' ' + message.receiver_last_name)?.toLowerCase().includes(searchTerm);
                
                const matchesStatus = !statusFilter || 
                    (statusFilter === 'read' && message.is_read) ||
                    (statusFilter === 'unread' && !message.is_read);
                
                return matchesSearch && matchesStatus;
            });
        }
        
        // Display filtered data
        const container = document.getElementById(section + 'Table');
        if (container) {
            displaySectionData(section, filteredData);
        }
    };
    
    // Export functions
    window.exportUsers = function() {
        exportData('users', 'users_export.csv');
    };
    
    window.exportJobs = function() {
        exportData('jobs', 'jobs_export.csv');
    };
    
    window.exportRequests = function() {
        exportData('requests', 'requests_export.csv');
    };
    
    window.exportMessages = function() {
        exportData('messages', 'messages_export.csv');
    };
    
    function exportData(section, filename) {
        const data = getCacheData(section);
        if (!data || data.length === 0) {
            alert('هیچ داده‌ای برای صادرات وجود ندارد');
            return;
        }
        
        let csv = '';
        let headers = [];
        
        if (section === 'users') {
            headers = ['شناسه', 'نام', 'نام خانوادگی', 'ایمیل', 'نوع کاربر', 'شرکت', 'تاریخ عضویت'];
            csv = headers.join(',') + '\n';
            data.forEach(user => {
                csv += `${user.user_id},"${user.first_name}","${user.last_name}","${user.email}","${getUserTypeText(user.user_type)}","${user.company_name || ''}","${formatDate(user.created_at)}"\n`;
            });
        } else if (section === 'jobs') {
            headers = ['شناسه', 'عنوان', 'کارفرما', 'بودجه', 'تاریخ ایجاد'];
            csv = headers.join(',') + '\n';
            data.forEach(job => {
                csv += `${job.job_id},"${job.title}","${job.first_name} ${job.last_name}","${formatBudget(job.budget_type, job.budget_min, job.budget_max)}","${formatDate(job.created_at)}"\n`;
            });
        }
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }
    
    // Enhanced User Search Setup
    function setupEnhancedUserSearch() {
        const userSearch = document.getElementById('userSearch');
        const clearSearch = document.getElementById('clearSearch');
        
        if (userSearch) {
            userSearch.addEventListener('input', function() {
                if (this.value.length > 0) {
                    clearSearch?.classList.remove('hidden');
                } else {
                    clearSearch?.classList.add('hidden');
                }
                filterAndDisplayUsers();
            });
        }
        
        // Advanced search controls
        const userTypeFilter = document.getElementById('userTypeFilter');
        const userSortBy = document.getElementById('userSortBy');
        const userPerPage = document.getElementById('userPerPage');
        
        if (userTypeFilter) userTypeFilter.addEventListener('change', filterAndDisplayUsers);
        if (userSortBy) userSortBy.addEventListener('change', filterAndDisplayUsers);
        if (userPerPage) userPerPage.addEventListener('change', filterAndDisplayUsers);
    }
    
    // Enhanced Job Search Setup
    function setupEnhancedJobSearch() {
        console.log('💼 Setting up enhanced job search...');
        
        if (typeof initEnhancedJobSearch === 'function') {
            try {
                initEnhancedJobSearch();
                console.log('✅ Enhanced job search initialized');
            } catch (error) {
                console.error('❌ Error initializing enhanced job search:', error);
            }
        }
        
        const jobSearchInput = document.getElementById('jobSearch');
        const clearJobSearch = document.getElementById('clearJobSearch');
        
        if (jobSearchInput) {
            jobSearchInput.addEventListener('input', function() {
                if (this.value.length > 0) {
                    clearJobSearch?.classList.remove('hidden');
                } else {
                    clearJobSearch?.classList.add('hidden');
                }
                if (typeof performEnhancedJobSearch === 'function') {
                    performEnhancedJobSearch();
                } else {
                    filterData('jobs');
                }
            });
        }
        
        const jobStatusFilters = document.querySelectorAll('.job-status-filter');
        jobStatusFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                if (typeof applyJobStatusFilter === 'function') {
                    applyJobStatusFilter(this.dataset.status);
                }
            });
        });
    }
    
    // Global variables for user management
    window.currentUserView = 'grid';
    window.currentUserPage = 1;
    window.userFilters = {
        search: '',
        type: '',
        sortBy: 'created_at_desc',
        perPage: 12,
        quickFilter: 'all'
    };

    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(userForm);
            
            // Validation
            const email = formData.get('email');
            const password = formData.get('password');
            const userType = formData.get('user_type');
            
            if (!email || !formData.get('first_name') || !formData.get('last_name') || !userType || !password) {
                alert('لطفاً تمام فیلدهای اجباری را پر کنید');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('لطفاً یک ایمیل معتبر وارد کنید');
                return;
            }
            
            const data = {
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                email: email,
                password: password,
                user_type: userType,
                company_name: formData.get('company_name')
            };
            
            try {
                const response = await fetch(`${getApiEndpoint()}?action=user`, {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + adminToken,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    const result = await response.json();
                    let message = result.message;
                    if (result.reused_id) {
                        message += `\n\nآیدی بازاستفاده شده: ${result.reused_id}`;
                    }
                    alert(message);
                    closeUserModal();
                    loadSectionData('users');
                    loadStats();
                    loadNullIdsStats();
                } else {
                    const error = await response.json();
                    alert(error.message || 'خطا در ایجاد کاربر');
                }
            } catch (error) {
                console.error('Error creating user:', error);
                alert('خطا در اتصال به سرور');
            }
        });
    }
    
    // Handle job form submission
    const jobForm = document.getElementById('jobForm');
    if (jobForm) {
        // Add budget type change listener
        const budgetType = document.getElementById('budgetType');
        if (budgetType) {
            budgetType.addEventListener('change', function() {
                toggleBudgetFields(this.value);
            });
        }
        
        jobForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(jobForm);
            
            // Validation
            const title = formData.get('title');
            const description = formData.get('description');
            const budgetType = formData.get('budget_type');
            const userId = formData.get('user_id');
            
            if (!title || !description || !budgetType || !userId) {
                alert('لطفاً تمام فیلدهای اجباری را پر کنید');
                return;
            }
            
            // Budget validation
            if (budgetType === 'fixed' || budgetType === 'range') {
                const budgetMin = formData.get('budget_min');
                if (!budgetMin || budgetMin <= 0) {
                    alert('لطفاً مبلغ بودجه را وارد کنید');
                    return;
                }
            }
            
            if (budgetType === 'range') {
                const budgetMax = formData.get('budget_max');
                const budgetMin = formData.get('budget_min');
                if (!budgetMax || budgetMax <= 0 || parseInt(budgetMax) <= parseInt(budgetMin)) {
                    alert('حداکثر بودجه باید بیشتر از حداقل بودجه باشد');
                    return;
                }
            }
            
            const data = {
                title: title,
                description: description,
                budget_type: budgetType,
                user_id: userId
            };
            
            // Add budget values based on type
            if (budgetType === 'fixed' || budgetType === 'range') {
                data.budget_min = formData.get('budget_min');
            }
            if (budgetType === 'range') {
                data.budget_max = formData.get('budget_max');
            }
            
            try {
                const response = await fetch(`${getApiEndpoint()}?action=job`, {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + adminToken,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    const result = await response.json();
                    let message = result.message;
                    if (result.reused_id) {
                        message += `\n\nآیدی بازاستفاده شده: ${result.reused_id}`;
                    }
                    alert(message);
                    closeJobModal();
                    loadSectionData('jobs');
                    loadStats();
                    loadNullIdsStats();
                } else {
                    const error = await response.json();
                    alert(error.message || 'خطا در ایجاد آگهی');
                }
            } catch (error) {
                console.error('Error creating job:', error);
                alert('خطا در اتصال به سرور');
            }
        });
    }
    
    // Close modals when clicking outside
    const userModal = document.getElementById('userModal');
    if (userModal) {
        userModal.addEventListener('click', function(e) {
            if (e.target === userModal) {
                closeUserModal();
            }
        });
    }
    
    const jobModal = document.getElementById('jobModal');
    if (jobModal) {
        jobModal.addEventListener('click', function(e) {
            if (e.target === jobModal) {
                closeJobModal();
            }
        });
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (!userModal.classList.contains('hidden')) {
                closeUserModal();
            }
            if (!jobModal.classList.contains('hidden')) {
                closeJobModal();
            }
            if (!jobDetailsModal.classList.contains('hidden')) {
                closeJobDetailsModal();
            }
        }
    });
    
    // Close job details modal when clicking outside
    const jobDetailsModal = document.getElementById('jobDetailsModal');
    if (jobDetailsModal) {
        jobDetailsModal.addEventListener('click', function(e) {
            if (e.target === jobDetailsModal) {
                closeJobDetailsModal();
            }
        });
    }
});
