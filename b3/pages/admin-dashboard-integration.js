// Admin Dashboard Integration Script
// Ensures proper integration between enhanced search system and existing dashboard

// Integration initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Initializing Dashboard Integration...');
    
    // Wait for all scripts to load
    setTimeout(() => {
        initializeDashboardIntegration();
    }, 100);
});

function initializeDashboardIntegration() {
    // Override the original showSection function to work with enhanced search
    const originalShowSection = window.showSection;
    
    window.showSection = function(section, reload = true) {
        console.log(`📋 Switching to section: ${section}`);
        
        // Call original function
        if (originalShowSection) {
            originalShowSection(section, reload);
        }
        
        // Initialize enhanced search for users section
        if (section === 'users') {
            setTimeout(() => {
                if (typeof initEnhancedSearch === 'function') {
                    initEnhancedSearch();
                    
                    // Load initial data
                    if (typeof performEnhancedUserSearch === 'function') {
                        performEnhancedUserSearch();
                    }
                }
            }, 200);
        }
        
        // Initialize enhanced search for jobs section
        if (section === 'jobs') {
            setTimeout(() => {
                if (typeof initEnhancedJobSearch === 'function') {
                    initEnhancedJobSearch();
                    
                    // Load initial data
                    if (typeof performEnhancedJobSearch === 'function') {
                        performEnhancedJobSearch();
                    }
                }
            }, 200);
        }
    };
    
    // Override displayUsers function to use enhanced rendering
    window.displayUsers = function(container, users) {
        if (typeof renderEnhancedUserResults === 'function') {
            // Update the enhanced search state with new data
            if (enhancedSearchState && users) {
                enhancedSearchState.originalData.users = users;
                enhancedSearchState.filteredData.users = users;
                enhancedSearchState.users.pagination.totalItems = users.length;
            }
            
            renderEnhancedUserResults();
            updateEnhancedPagination();
            updateResultCount();
        } else {
            // Fallback to original implementation
            console.warn('Enhanced rendering not available, using fallback');
            container.innerHTML = users.map(user => `
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-bold">${user.first_name} ${user.last_name}</h3>
                    <p class="text-gray-600">${user.email}</p>
                    <span class="text-sm text-blue-600">${user.user_type}</span>
                </div>
            `).join('');
        }
    };
    
    // Override displayJobs function to use enhanced rendering
    window.displayJobs = function(container, jobs) {
        if (typeof renderEnhancedJobResults === 'function') {
            // Update the enhanced job search state with new data
            if (jobSearchState && jobs) {
                jobSearchState.originalData.jobs = jobs;
                jobSearchState.filteredData.jobs = jobs;
                jobSearchState.jobs.pagination.totalItems = jobs.length;
            }
            
            renderEnhancedJobResults();
            updateEnhancedJobPagination();
            updateJobResultCount();
        } else {
            // Fallback to original implementation
            console.warn('Enhanced job rendering not available, using fallback');
            container.innerHTML = jobs.map(job => `
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-bold">${job.title}</h3>
                    <p class="text-gray-600">${job.employer_name}</p>
                    <span class="text-sm text-green-600">${job.status}</span>
                </div>
            `).join('');
        }
    };
    
    // Add CSS for active view buttons
    const style = document.createElement('style');
    style.textContent = `
        .view-btn.active {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .quick-filter-tag.active {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            border-color: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .search-loading {
            opacity: 0.6;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
    
    console.log('✅ Dashboard Integration Complete');
}

// Enhanced quick filter function with proper integration
window.quickFilterUsers = function(filterType) {
    console.log(`🏷️ Quick filter triggered: ${filterType}`);
    
    if (typeof applyQuickFilter === 'function') {
        applyQuickFilter(filterType);
    } else {
        console.warn('Enhanced quick filter not available');
        // Fallback implementation
        const buttons = document.querySelectorAll('.quick-filter-tag');
        buttons.forEach(btn => btn.classList.remove('active'));
        
        const activeButton = document.querySelector(`[data-filter="${filterType}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }
        
        // Trigger data reload with filter
        if (window.loadSectionData) {
            loadSectionData('users', false);
        }
    }
};

// Enhanced export function
window.exportUsers = function() {
    console.log('📤 Exporting users...');
    
    let usersToExport = [];
    
    if (enhancedSearchState && enhancedSearchState.filteredData.users.length > 0) {
        usersToExport = enhancedSearchState.filteredData.users;
    } else if (dataCache && dataCache.users) {
        usersToExport = dataCache.users;
    } else {
        console.warn('No user data available for export');
        return;
    }
    
    // Create CSV content
    const headers = ['شناسه', 'نام', 'نام خانوادگی', 'ایمیل', 'نوع کاربر', 'نام شرکت', 'تاریخ عضویت'];
    const csvContent = [
        headers.join(','),
        ...usersToExport.map(user => [
            user.user_id,
            user.first_name || '',
            user.last_name || '',
            user.email || '',
            user.user_type || '',
            user.company_name || '',
            user.created_at || ''
        ].map(field => `"${field}"`).join(','))
    ].join('\n');
    
    // Download CSV
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `users_export_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('✅ Users exported successfully');
};

// Enhanced job export function
window.exportJobs = function() {
    console.log('📤 Exporting jobs...');
    
    let jobsToExport = [];
    
    if (jobSearchState && jobSearchState.filteredData.jobs.length > 0) {
        jobsToExport = jobSearchState.filteredData.jobs;
    } else if (dataCache && dataCache.jobs) {
        jobsToExport = dataCache.jobs;
    } else {
        console.warn('No job data available for export');
        return;
    }
    
    // Create CSV content
    const headers = ['شناسه', 'عنوان', 'توضیحات', 'کارفرما', 'وضعیت', 'نوع بودجه', 'حداقل بودجه', 'حداکثر بودجه', 'تاریخ ایجاد'];
    const csvContent = [
        headers.join(','),
        ...jobsToExport.map(job => [
            job.job_id,
            job.title || '',
            (job.description || '').replace(/[\r\n]+/g, ' ').substring(0, 100),
            job.employer_name || '',
            job.status || '',
            job.budget_type || '',
            job.budget_min || '',
            job.budget_max || '',
            job.created_at || ''
        ].map(field => `"${field}"`).join(','))
    ].join('\n');
    
    // Download CSV
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `jobs_export_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('✅ Jobs exported successfully');
};

// Integration helper functions
function ensureEnhancedSearchLoaded() {
    return new Promise((resolve) => {
        const checkInterval = setInterval(() => {
            if (typeof initEnhancedSearch === 'function' && 
                typeof performEnhancedUserSearch === 'function' &&
                typeof renderEnhancedUserResults === 'function') {
                clearInterval(checkInterval);
                resolve(true);
            }
        }, 100);
        
        // Timeout after 5 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
            resolve(false);
        }, 5000);
    });
}

// Debug helper
window.debugEnhancedSearch = function() {
    console.log('🐛 Enhanced Search Debug Info:');
    console.log('Search State:', enhancedSearchState);
    console.log('Available Functions:', {
        initEnhancedSearch: typeof initEnhancedSearch,
        performEnhancedUserSearch: typeof performEnhancedUserSearch,
        renderEnhancedUserResults: typeof renderEnhancedUserResults,
        applyQuickFilter: typeof applyQuickFilter
    });
    console.log('DOM Elements:', {
        userSearch: !!document.getElementById('userSearch'),
        usersTable: !!document.getElementById('usersTable'),
        usersPagination: !!document.getElementById('usersPagination'),
        quickFilterTags: document.querySelectorAll('.quick-filter-tag').length
    });
};

console.log('📋 Dashboard Integration Script Loaded');
