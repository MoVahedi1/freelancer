// Enhanced Database-Connected Search System for Admin Dashboard
// Real-time search, advanced filtering, and user categorization

// Global search state management
let enhancedSearchState = {
    users: {
        searchTerm: '',
        filters: {
            userType: 'all',
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
    originalData: { users: [] },
    filteredData: { users: [] }
};

// Initialize enhanced search system
function initEnhancedSearch() {
    console.log('🔍 Initializing Enhanced Search System...');
    
    setupSearchInputHandlers();
    setupFilterHandlers();
    setupQuickFilterButtons();
    setupViewToggle();
    setupAdvancedSearchPanel();
    
    console.log('✅ Enhanced Search System Ready');
}

// Setup real-time search with debouncing
function setupSearchInputHandlers() {
    const searchInput = document.getElementById('userSearch');
    const clearButton = document.getElementById('clearSearch');
    
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                enhancedSearchState.users.searchTerm = e.target.value.trim();
                enhancedSearchState.users.pagination.currentPage = 1;
                performEnhancedUserSearch();
                updateClearButtonVisibility();
            }, 300);
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                enhancedSearchState.users.searchTerm = e.target.value.trim();
                enhancedSearchState.users.pagination.currentPage = 1;
                performEnhancedUserSearch();
            }
        });
    }
    
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            enhancedSearchState.users.searchTerm = '';
            enhancedSearchState.users.pagination.currentPage = 1;
            performEnhancedUserSearch();
            updateClearButtonVisibility();
        });
    }
}

// Update clear button visibility
function updateClearButtonVisibility() {
    const clearButton = document.getElementById('clearSearch');
    const searchInput = document.getElementById('userSearch');
    
    if (clearButton && searchInput) {
        clearButton.style.display = searchInput.value.trim() ? 'block' : 'none';
    }
}

// Setup filter handlers for advanced search
function setupFilterHandlers() {
    const userTypeFilter = document.getElementById('userTypeFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');
    const sortByFilter = document.getElementById('sortByFilter');
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    
    if (userTypeFilter) {
        userTypeFilter.addEventListener('change', function(e) {
            enhancedSearchState.users.filters.userType = e.target.value;
            enhancedSearchState.users.pagination.currentPage = 1;
            performEnhancedUserSearch();
        });
    }
    
    if (dateRangeFilter) {
        dateRangeFilter.addEventListener('change', function(e) {
            enhancedSearchState.users.filters.dateRange = e.target.value;
            enhancedSearchState.users.pagination.currentPage = 1;
            performEnhancedUserSearch();
        });
    }
    
    if (sortByFilter) {
        sortByFilter.addEventListener('change', function(e) {
            enhancedSearchState.users.filters.sortBy = e.target.value;
            performEnhancedUserSearch();
        });
    }
    
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function(e) {
            enhancedSearchState.users.pagination.itemsPerPage = parseInt(e.target.value);
            enhancedSearchState.users.pagination.currentPage = 1;
            performEnhancedUserSearch();
        });
    }
}

// Setup quick filter buttons for user categories
function setupQuickFilterButtons() {
    const quickFilterButtons = document.querySelectorAll('.quick-filter-tag');
    
    quickFilterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterType = this.getAttribute('data-filter');
            
            // Update active state
            quickFilterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Apply quick filter
            applyQuickFilter(filterType);
        });
    });
}

// Apply quick filter for user categories
function applyQuickFilter(filterType) {
    console.log(`🏷️ Applying quick filter: ${filterType}`);
    
    // Reset search and pagination
    enhancedSearchState.users.searchTerm = '';
    enhancedSearchState.users.pagination.currentPage = 1;
    
    // Clear search input
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Apply filter based on category
    switch(filterType) {
        case 'all':
            enhancedSearchState.users.filters.userType = 'all';
            enhancedSearchState.users.filters.dateRange = 'all';
            break;
        case 'employer':
            enhancedSearchState.users.filters.userType = 'employer';
            enhancedSearchState.users.filters.dateRange = 'all';
            break;
        case 'freelancer':
            enhancedSearchState.users.filters.userType = 'freelancer';
            enhancedSearchState.users.filters.dateRange = 'all';
            break;
        case 'admin':
            enhancedSearchState.users.filters.userType = 'admin';
            enhancedSearchState.users.filters.dateRange = 'all';
            break;
        case 'recent':
            enhancedSearchState.users.filters.userType = 'all';
            enhancedSearchState.users.filters.dateRange = 'week';
            break;
    }
    
    // Update filter selects
    updateFilterSelects();
    
    // Perform search
    performEnhancedUserSearch();
}

// Update filter select elements
function updateFilterSelects() {
    const userTypeFilter = document.getElementById('userTypeFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');
    
    if (userTypeFilter) {
        userTypeFilter.value = enhancedSearchState.users.filters.userType;
    }
    
    if (dateRangeFilter) {
        dateRangeFilter.value = enhancedSearchState.users.filters.dateRange;
    }
}

// Setup view toggle (grid/list)
function setupViewToggle() {
    const gridViewBtn = document.getElementById('gridViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    
    if (gridViewBtn) {
        gridViewBtn.addEventListener('click', function() {
            enhancedSearchState.users.view = 'grid';
            updateViewButtons();
            renderEnhancedUserResults();
        });
    }
    
    if (listViewBtn) {
        listViewBtn.addEventListener('click', function() {
            enhancedSearchState.users.view = 'list';
            updateViewButtons();
            renderEnhancedUserResults();
        });
    }
}

// Update view buttons state
function updateViewButtons() {
    const gridViewBtn = document.getElementById('gridViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    
    if (gridViewBtn && listViewBtn) {
        gridViewBtn.classList.toggle('active', enhancedSearchState.users.view === 'grid');
        listViewBtn.classList.toggle('active', enhancedSearchState.users.view === 'list');
    }
}

// Setup advanced search panel toggle
function setupAdvancedSearchPanel() {
    const toggleButton = document.getElementById('toggleAdvancedSearch');
    const panel = document.getElementById('advancedSearchPanel');
    
    if (toggleButton && panel) {
        toggleButton.addEventListener('click', function() {
            const isHidden = panel.classList.contains('hidden');
            
            if (isHidden) {
                panel.classList.remove('hidden');
                panel.style.maxHeight = panel.scrollHeight + 'px';
                toggleButton.innerHTML = '<i class="fas fa-chevron-up ml-2"></i>جستجوی ساده';
            } else {
                panel.style.maxHeight = '0';
                setTimeout(() => {
                    panel.classList.add('hidden');
                }, 300);
                toggleButton.innerHTML = '<i class="fas fa-chevron-down ml-2"></i>جستجوی پیشرفته';
            }
        });
    }
}

// Main search function with database connection
async function performEnhancedUserSearch() {
    console.log('🔍 Performing enhanced user search...', enhancedSearchState.users);
    
    try {
        showSearchLoading(true);
        
        // Load fresh data from database
        await loadUserDataFromDatabase();
        
        // Apply all filters
        const filteredUsers = applyAllUserFilters(enhancedSearchState.originalData.users);
        
        // Update state
        enhancedSearchState.filteredData.users = filteredUsers;
        enhancedSearchState.users.pagination.totalItems = filteredUsers.length;
        
        // Render results
        renderEnhancedUserResults();
        updateEnhancedPagination();
        updateResultCount();
        
        showSearchLoading(false);
        
        console.log(`✅ Search completed. Found ${filteredUsers.length} users.`);
        
    } catch (error) {
        console.error('❌ Search error:', error);
        showSearchError('خطا در جستجو. لطفاً دوباره تلاش کنید.');
        showSearchLoading(false);
    }
}

// Load user data from database via API
async function loadUserDataFromDatabase() {
    // Check if we have valid cached data
    if (enhancedSearchState.originalData.users.length > 0 && isCacheValid('users')) {
        return;
    }
    
    console.log('📡 Loading fresh user data from database...');
    
    try {
        const response = await fetch(getApiEndpoint() + '?action=users', {
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
        
        if (data.users) {
            enhancedSearchState.originalData.users = data.users;
            setCacheData('users', data.users);
            console.log(`✅ Loaded ${data.users.length} users from database`);
        } else {
            throw new Error('Invalid response format');
        }
        
    } catch (error) {
        console.error('❌ Error loading user data:', error);
        throw error;
    }
}

// Apply all filters to user data
function applyAllUserFilters(users) {
    let filtered = [...users];
    
    // Apply search term filter
    if (enhancedSearchState.users.searchTerm) {
        const searchTerm = enhancedSearchState.users.searchTerm.toLowerCase();
        filtered = filtered.filter(user => {
            return (
                (user.first_name && user.first_name.toLowerCase().includes(searchTerm)) ||
                (user.last_name && user.last_name.toLowerCase().includes(searchTerm)) ||
                (user.email && user.email.toLowerCase().includes(searchTerm)) ||
                (user.company_name && user.company_name.toLowerCase().includes(searchTerm))
            );
        });
    }
    
    // Apply user type filter
    if (enhancedSearchState.users.filters.userType !== 'all') {
        filtered = filtered.filter(user => {
            if (enhancedSearchState.users.filters.userType === 'admin') {
                return user.user_type === 'admin' || user.email.includes('admin');
            }
            return user.user_type === enhancedSearchState.users.filters.userType;
        });
    }
    
    // Apply date range filter
    if (enhancedSearchState.users.filters.dateRange !== 'all') {
        const now = new Date();
        const filterDate = new Date();
        
        switch (enhancedSearchState.users.filters.dateRange) {
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
        
        filtered = filtered.filter(user => {
            const userDate = new Date(user.created_at);
            return userDate >= filterDate;
        });
    }
    
    // Apply sorting
    filtered.sort((a, b) => {
        let aValue, bValue;
        
        switch (enhancedSearchState.users.filters.sortBy) {
            case 'name':
                aValue = (a.first_name + ' ' + a.last_name).toLowerCase();
                bValue = (b.first_name + ' ' + b.last_name).toLowerCase();
                break;
            case 'email':
                aValue = a.email.toLowerCase();
                bValue = b.email.toLowerCase();
                break;
            case 'created_at':
            default:
                aValue = new Date(a.created_at);
                bValue = new Date(b.created_at);
                break;
        }
        
        if (enhancedSearchState.users.filters.sortOrder === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    return filtered;
}

// Show/hide search loading state
function showSearchLoading(show) {
    const loadingElement = document.getElementById('searchLoading');
    const resultsContainer = document.getElementById('usersTable');
    
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

// Show search error message
function showSearchError(message) {
    const container = document.getElementById('usersTable');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="w-24 h-24 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">خطا در بارگذاری</h3>
                <p class="text-gray-600 mb-6">${message}</p>
                <button onclick="performEnhancedUserSearch()" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition-all duration-200">
                    <i class="fas fa-refresh ml-2"></i>
                    تلاش مجدد
                </button>
            </div>
        `;
    }
}

// Initialize enhanced search when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize enhanced search system
    initEnhancedSearch();
    
    // Load initial data
    if (currentSection === 'users') {
        performEnhancedUserSearch();
    }
});

// Export functions for global access
window.performEnhancedUserSearch = performEnhancedUserSearch;
window.applyQuickFilter = applyQuickFilter;
window.clearAllFilters = function() {
    enhancedSearchState.users.searchTerm = '';
    enhancedSearchState.users.filters.userType = 'all';
    enhancedSearchState.users.filters.dateRange = 'all';
    enhancedSearchState.users.pagination.currentPage = 1;
    
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.value = '';
    }
    
    updateFilterSelects();
    
    const quickFilterButtons = document.querySelectorAll('.quick-filter-tag');
    quickFilterButtons.forEach(btn => btn.classList.remove('active'));
    const allButton = document.querySelector('.quick-filter-tag[data-filter="all"]');
    if (allButton) {
        allButton.classList.add('active');
    }
    
    performEnhancedUserSearch();
};
