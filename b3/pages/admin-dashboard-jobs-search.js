// Enhanced Job Search and Filter Functionality for Admin Dashboard
// Database-connected search, filtering, and status management for jobs

// Global job search state
let jobSearchState = {
    jobs: {
        searchTerm: '',
        filters: {
            status: 'all',
            dateRange: 'all',
            sortBy: 'created_at',
            sortOrder: 'desc'
        },
        pagination: {
            currentPage: 1,
            itemsPerPage: 12,
            totalItems: 0
        },
        view: 'grid'
    },
    originalData: { jobs: [] },
    filteredData: { jobs: [] }
};

// Initialize enhanced job search system
function initEnhancedJobSearch() {
    console.log('💼 Initializing Enhanced Job Search System...');
    
    setupJobSearchInputHandlers();
    setupJobFilterHandlers();
    setupJobStatusFilters();
    setupJobViewToggle();
    setupJobAdvancedSearchPanel();
    
    console.log('✅ Enhanced Job Search System Ready');
}

// Setup real-time job search with debouncing
function setupJobSearchInputHandlers() {
    const searchInput = document.getElementById('jobSearch');
    const clearButton = document.getElementById('clearJobSearch');
    
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                jobSearchState.jobs.searchTerm = e.target.value.trim();
                jobSearchState.jobs.pagination.currentPage = 1;
                performEnhancedJobSearch();
                updateJobClearButtonVisibility();
            }, 300);
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                jobSearchState.jobs.searchTerm = e.target.value.trim();
                jobSearchState.jobs.pagination.currentPage = 1;
                performEnhancedJobSearch();
            }
        });
    }
    
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            jobSearchState.jobs.searchTerm = '';
            jobSearchState.jobs.pagination.currentPage = 1;
            performEnhancedJobSearch();
            updateJobClearButtonVisibility();
        });
    }
}

// Update clear button visibility
function updateJobClearButtonVisibility() {
    const clearButton = document.getElementById('clearJobSearch');
    const searchInput = document.getElementById('jobSearch');
    
    if (clearButton && searchInput) {
        clearButton.style.display = searchInput.value.trim() ? 'block' : 'none';
    }
}

// Setup filter handlers for simplified job search
function setupJobFilterHandlers() {
    // Advanced search panel elements have been removed from UI
    // Only basic search and status filters remain active
    console.log('ℹ️ Advanced filter handlers removed - using simplified search only');
    
    // Items per page selector might still exist in pagination
    const itemsPerPageSelect = document.getElementById('jobItemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function(e) {
            jobSearchState.jobs.pagination.itemsPerPage = parseInt(e.target.value);
            jobSearchState.jobs.pagination.currentPage = 1;
            performEnhancedJobSearch();
        });
    }
}

// Setup job status filter buttons
function setupJobStatusFilters() {
    const statusFilterButtons = document.querySelectorAll('.job-status-filter-tag');
    
    statusFilterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterType = this.getAttribute('data-status');
            
            // Update active state
            statusFilterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Apply status filter
            applyJobStatusFilter(filterType);
        });
    });
}

// Apply job status filter
function applyJobStatusFilter(statusType) {
    console.log(`🏷️ Applying job status filter: ${statusType}`);
    
    // Reset search and pagination
    jobSearchState.jobs.searchTerm = '';
    jobSearchState.jobs.pagination.currentPage = 1;
    
    // Clear search input
    const searchInput = document.getElementById('jobSearch');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Apply filter based on status
    switch(statusType) {
        case 'all':
            jobSearchState.jobs.filters.status = 'all';
            break;
        case 'active':
            jobSearchState.jobs.filters.status = 'active';
            break;
        case 'completed':
            jobSearchState.jobs.filters.status = 'completed';
            break;
        case 'cancelled':
            jobSearchState.jobs.filters.status = 'cancelled';
            break;
    }
    
    // Update filter selects
    updateJobFilterSelects();
    
    // Perform search
    performEnhancedJobSearch();
}

// Update job filter select elements (simplified - advanced panel removed)
function updateJobFilterSelects() {
    // Advanced search panel elements have been removed from UI
    // This function is kept for compatibility but does nothing
    console.log('ℹ️ Filter select elements removed from UI');
}

// Setup job view toggle (grid/list)
function setupJobViewToggle() {
    const gridViewBtn = document.getElementById('jobGridViewBtn');
    const listViewBtn = document.getElementById('jobListViewBtn');
    
    if (gridViewBtn) {
        gridViewBtn.addEventListener('click', function() {
            jobSearchState.jobs.view = 'grid';
            updateJobViewButtons();
            renderEnhancedJobResults();
        });
    }
    
    if (listViewBtn) {
        listViewBtn.addEventListener('click', function() {
            jobSearchState.jobs.view = 'list';
            updateJobViewButtons();
            renderEnhancedJobResults();
        });
    }
}

// Update job view buttons state
function updateJobViewButtons() {
    const gridViewBtn = document.getElementById('jobGridViewBtn');
    const listViewBtn = document.getElementById('jobListViewBtn');
    
    if (gridViewBtn && listViewBtn) {
        gridViewBtn.classList.toggle('active', jobSearchState.jobs.view === 'grid');
        listViewBtn.classList.toggle('active', jobSearchState.jobs.view === 'list');
    }
}

// Setup advanced job search panel toggle (removed - UI elements no longer exist)
function setupJobAdvancedSearchPanel() {
    console.log('ℹ️ Advanced search panel functionality removed from UI');
    // Advanced search panel has been removed from the UI
    // This function is kept for compatibility but does nothing
}

// Main job search function with database connection
async function performEnhancedJobSearch() {
    console.log('💼 Performing enhanced job search...', jobSearchState.jobs);
    
    try {
        showJobSearchLoading(true);
        
        // Load fresh data from database
        await loadJobDataFromDatabase();
        
        // Apply all filters
        const filteredJobs = applyAllJobFilters(jobSearchState.originalData.jobs);
        
        // Update state
        jobSearchState.filteredData.jobs = filteredJobs;
        jobSearchState.jobs.pagination.totalItems = filteredJobs.length;
        
        // Render results
        renderEnhancedJobResults();
        updateEnhancedJobPagination();
        updateJobResultCount();
        
        showJobSearchLoading(false);
        
        console.log(`✅ Job search completed. Found ${filteredJobs.length} jobs.`);
        
    } catch (error) {
        console.error('❌ Job search error:', error);
        showJobSearchError('خطا در جستجوی آگهی‌ها. لطفاً دوباره تلاش کنید.');
        showJobSearchLoading(false);
    }
}

// Load job data from database via API
async function loadJobDataFromDatabase() {
    // Check if we have valid cached data
    if (jobSearchState.originalData.jobs.length > 0 && isCacheValid('jobs')) {
        return;
    }
    
    console.log('📡 Loading fresh job data from database...');
    
    try {
        const response = await fetch(getApiEndpoint() + '?action=jobs', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${adminToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.jobs) {
            jobSearchState.originalData.jobs = data.jobs;
            setCacheData('jobs', data.jobs);
            console.log(`✅ Loaded ${data.jobs.length} jobs from database`);
        } else {
            throw new Error('Invalid response format');
        }
        
    } catch (error) {
        console.error('❌ Error loading job data:', error);
        throw error;
    }
}

// Apply all filters to job data
function applyAllJobFilters(jobs) {
    let filtered = [...jobs];
    
    // Apply search term filter
    if (jobSearchState.jobs.searchTerm) {
        const searchTerm = jobSearchState.jobs.searchTerm.toLowerCase();
        filtered = filtered.filter(job => {
            return (
                (job.title && job.title.toLowerCase().includes(searchTerm)) ||
                (job.description && job.description.toLowerCase().includes(searchTerm)) ||
                (job.employer_name && job.employer_name.toLowerCase().includes(searchTerm))
            );
        });
    }
    
    // Apply status filter
    if (jobSearchState.jobs.filters.status !== 'all') {
        filtered = filtered.filter(job => {
            return job.status === jobSearchState.jobs.filters.status;
        });
    }
    
    // Apply date range filter
    if (jobSearchState.jobs.filters.dateRange !== 'all') {
        const now = new Date();
        const filterDate = new Date();
        
        switch (jobSearchState.jobs.filters.dateRange) {
            case 'day':
                filterDate.setDate(now.getDate() - 1);
                break;
            case 'week':
                filterDate.setDate(now.getDate() - 7);
                break;
            case 'month':
                filterDate.setMonth(now.getMonth() - 1);
                break;
            case 'year':
                filterDate.setFullYear(now.getFullYear() - 1);
                break;
        }
        
        filtered = filtered.filter(job => {
            const jobDate = new Date(job.created_at);
            return jobDate >= filterDate;
        });
    }
    
    // Apply sorting
    filtered.sort((a, b) => {
        let aValue, bValue;
        
        switch (jobSearchState.jobs.filters.sortBy) {
            case 'title':
                aValue = a.title.toLowerCase();
                bValue = b.title.toLowerCase();
                break;
            case 'budget':
                aValue = parseFloat(a.budget_min || 0);
                bValue = parseFloat(b.budget_min || 0);
                break;
            case 'created_at':
            default:
                aValue = new Date(a.created_at);
                bValue = new Date(b.created_at);
                break;
        }
        
        if (jobSearchState.jobs.filters.sortOrder === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    return filtered;
}

// Show/hide job search loading state
function showJobSearchLoading(show) {
    const loadingElement = document.getElementById('jobSearchLoading');
    const resultsContainer = document.getElementById('jobsTable');
    
    if (show) {
        if (loadingElement) {
            loadingElement.classList.remove('hidden');
        }
        if (resultsContainer) {
            resultsContainer.style.opacity = '0.5';
        }
    } else {
        if (loadingElement) {
            loadingElement.classList.add('hidden');
        }
        if (resultsContainer) {
            resultsContainer.style.opacity = '1';
        }
    }
}

// Show job search error message
function showJobSearchError(message) {
    const container = document.getElementById('jobsTable');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="w-24 h-24 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">خطا در بارگذاری</h3>
                <p class="text-gray-600 mb-6">${message}</p>
                <button onclick="performEnhancedJobSearch()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg font-medium hover:from-green-600 hover:to-green-700 transition-all duration-200">
                    <i class="fas fa-refresh ml-2"></i>
                    تلاش مجدد
                </button>
            </div>
        `;
    }
}

// Get job status class for styling
function getJobStatusClass(status) {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'completed':
            return 'bg-blue-100 text-blue-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Get job status text in Persian
function getJobStatusText(status) {
    switch (status) {
        case 'active':
            return 'فعال';
        case 'completed':
            return 'تکمیل شده';
        case 'cancelled':
            return 'لغو شده';
        default:
            return 'نامشخص';
    }
}

// Clear all job filters
function clearAllJobFilters() {
    jobSearchState.jobs.searchTerm = '';
    jobSearchState.jobs.filters.status = 'all';
    jobSearchState.jobs.filters.dateRange = 'all';
    jobSearchState.jobs.pagination.currentPage = 1;
    
    const searchInput = document.getElementById('jobSearch');
    if (searchInput) {
        searchInput.value = '';
    }
    
    updateJobFilterSelects();
    
    const statusFilterButtons = document.querySelectorAll('.job-status-filter-tag');
    statusFilterButtons.forEach(btn => btn.classList.remove('active'));
    const allButton = document.querySelector('.job-status-filter-tag[data-status="all"]');
    if (allButton) {
        allButton.classList.add('active');
    }
    
    performEnhancedJobSearch();
}

// Export functions for global access
window.performEnhancedJobSearch = performEnhancedJobSearch;
window.applyJobStatusFilter = applyJobStatusFilter;
window.clearAllJobFilters = clearAllJobFilters;
window.initEnhancedJobSearch = initEnhancedJobSearch;

console.log('💼 Job Search System Loaded');
