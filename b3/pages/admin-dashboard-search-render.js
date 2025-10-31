// Enhanced Search Rendering and Pagination Functions
// Handles user display, pagination, and result management

// Render enhanced user search results
function renderEnhancedUserResults() {
    const container = document.getElementById('usersTable');
    if (!container) return;
    
    const { currentPage, itemsPerPage } = enhancedSearchState.users.pagination;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedUsers = enhancedSearchState.filteredData.users.slice(startIndex, endIndex);
    
    if (paginatedUsers.length === 0) {
        renderEmptyState(container);
        return;
    }
    
    if (enhancedSearchState.users.view === 'grid') {
        renderUsersGrid(container, paginatedUsers);
    } else {
        renderUsersList(container, paginatedUsers);
    }
}

// Render users in grid view
function renderUsersGrid(container, users) {
    container.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6';
    
    container.innerHTML = users.map(user => `
        <div class="data-card bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
            <div class="card-header bg-gradient-to-r from-blue-500/10 to-purple-500/10 p-4 rounded-t-2xl border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                            ${(user.first_name?.charAt(0) || 'U').toUpperCase()}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">${user.first_name || ''} ${user.last_name || ''}</h3>
                            <span class="inline-block px-2 py-1 text-xs rounded-full ${getUserTypeClass(user.user_type)}">
                                ${getUserTypeText(user.user_type)}
                            </span>
                        </div>
                    </div>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                        #${user.user_id}
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-envelope ml-2 text-blue-500"></i>
                        <span class="truncate">${user.email || 'نامشخص'}</span>
                    </div>
                    ${user.company_name ? `
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-building ml-2 text-green-500"></i>
                            <span class="truncate">${user.company_name}</span>
                        </div>
                    ` : ''}
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-calendar ml-2 text-purple-500"></i>
                        <span>${formatDate(user.created_at)}</span>
                    </div>
                </div>
            </div>
            <div class="card-footer p-4 pt-0">
                <div class="flex space-x-2 space-x-reverse">
                    <button onclick="viewUserDetails(${user.user_id})" 
                            class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:from-blue-600 hover:to-blue-700 transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-eye ml-1"></i>
                        مشاهده
                    </button>
                    <button onclick="deleteUser(${user.user_id})" 
                            class="bg-gradient-to-r from-red-500 to-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:from-red-600 hover:to-red-700 transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-trash ml-1"></i>
                        حذف
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Render users in list view
function renderUsersList(container, users) {
    container.className = 'space-y-3';
    
    container.innerHTML = users.map(user => `
        <div class="data-card bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 space-x-reverse flex-1">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                            ${(user.first_name?.charAt(0) || 'U').toUpperCase()}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 space-x-reverse mb-1">
                                <h3 class="font-bold text-gray-900 truncate">${user.first_name || ''} ${user.last_name || ''}</h3>
                                <span class="inline-block px-2 py-1 text-xs rounded-full ${getUserTypeClass(user.user_type)}">
                                    ${getUserTypeText(user.user_type)}
                                </span>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                    #${user.user_id}
                                </span>
                            </div>
                            <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-600">
                                <span class="flex items-center">
                                    <i class="fas fa-envelope ml-1 text-blue-500"></i>
                                    ${user.email || 'نامشخص'}
                                </span>
                                ${user.company_name ? `
                                    <span class="flex items-center">
                                        <i class="fas fa-building ml-1 text-green-500"></i>
                                        ${user.company_name}
                                    </span>
                                ` : ''}
                                <span class="flex items-center">
                                    <i class="fas fa-calendar ml-1 text-purple-500"></i>
                                    ${formatDate(user.created_at)}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="viewUserDetails(${user.user_id})" 
                                class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:from-blue-600 hover:to-blue-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-eye ml-1"></i>
                            مشاهده
                        </button>
                        <button onclick="deleteUser(${user.user_id})" 
                                class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:from-red-600 hover:to-red-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-trash ml-1"></i>
                            حذف
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Render empty state when no results found
function renderEmptyState(container) {
    container.className = 'text-center py-12';
    container.innerHTML = `
        <div class="max-w-md mx-auto">
            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-r from-gray-200 to-gray-300 rounded-full flex items-center justify-center">
                <i class="fas fa-search text-3xl text-gray-500"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">نتیجه‌ای یافت نشد</h3>
            <p class="text-gray-600 mb-6">متأسفانه هیچ کاربری با معیارهای جستجوی شما پیدا نشد.</p>
            <button onclick="clearAllFilters()" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition-all duration-200">
                <i class="fas fa-refresh ml-2"></i>
                پاک کردن فیلترها
            </button>
        </div>
    `;
}

// Update enhanced pagination
function updateEnhancedPagination() {
    const paginationContainer = document.getElementById('usersPagination');
    if (!paginationContainer) return;
    
    const { currentPage, itemsPerPage, totalItems } = enhancedSearchState.users.pagination;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let paginationHTML = '<div class="flex items-center justify-center space-x-2 space-x-reverse">';
    
    // Previous button
    paginationHTML += `
        <button onclick="changeEnhancedPage(${currentPage - 1})" 
                ${currentPage === 1 ? 'disabled' : ''}
                class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        paginationHTML += `
            <button onclick="changeEnhancedPage(1)" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-all duration-200">
                1
            </button>
        `;
        if (startPage > 2) {
            paginationHTML += '<span class="px-2 text-gray-500">...</span>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button onclick="changeEnhancedPage(${i})" 
                    class="px-3 py-2 rounded-lg border transition-all duration-200 ${
                        i === currentPage 
                            ? 'bg-blue-500 text-white border-blue-500' 
                            : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                    }">
                ${i}
            </button>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += '<span class="px-2 text-gray-500">...</span>';
        }
        paginationHTML += `
            <button onclick="changeEnhancedPage(${totalPages})" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-all duration-200">
                ${totalPages}
            </button>
        `;
    }
    
    // Next button
    paginationHTML += `
        <button onclick="changeEnhancedPage(${currentPage + 1})" 
                ${currentPage === totalPages ? 'disabled' : ''}
                class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    paginationHTML += '</div>';
    
    paginationContainer.innerHTML = paginationHTML;
}

// Change page in enhanced pagination
function changeEnhancedPage(page) {
    const totalPages = Math.ceil(enhancedSearchState.users.pagination.totalItems / enhancedSearchState.users.pagination.itemsPerPage);
    
    if (page < 1 || page > totalPages) return;
    
    enhancedSearchState.users.pagination.currentPage = page;
    renderEnhancedUserResults();
    updateEnhancedPagination();
    
    // Scroll to top of results
    const usersSection = document.getElementById('users-section');
    if (usersSection) {
        usersSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Update result count display
function updateResultCount() {
    const resultCountElement = document.getElementById('userResultCount');
    if (resultCountElement) {
        const { totalItems } = enhancedSearchState.users.pagination;
        const { currentPage, itemsPerPage } = enhancedSearchState.users.pagination;
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);
        
        if (totalItems === 0) {
            resultCountElement.textContent = 'هیچ نتیجه‌ای یافت نشد';
        } else {
            resultCountElement.textContent = `نمایش ${startItem} تا ${endItem} از ${totalItems} کاربر`;
        }
    }
}

// View user details modal
function viewUserDetails(userId) {
    const user = enhancedSearchState.originalData.users.find(u => u.user_id == userId);
    if (!user) {
        console.error('User not found:', userId);
        return;
    }
    
    // Create and show user details modal
    const modalHTML = `
        <div id="userDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-900">جزئیات کاربر</h3>
                        <button onclick="closeUserDetailsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center space-x-4 space-x-reverse mb-6">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-2xl">
                            ${(user.first_name?.charAt(0) || 'U').toUpperCase()}
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">${user.first_name || ''} ${user.last_name || ''}</h2>
                            <span class="inline-block px-3 py-1 text-sm rounded-full ${getUserTypeClass(user.user_type)}">
                                ${getUserTypeText(user.user_type)}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">شناسه کاربر</label>
                                <p class="text-gray-900">#${user.user_id}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">ایمیل</label>
                                <p class="text-gray-900">${user.email || 'نامشخص'}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">نام</label>
                                <p class="text-gray-900">${user.first_name || 'نامشخص'}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">نام خانوادگی</label>
                                <p class="text-gray-900">${user.last_name || 'نامشخص'}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">نوع کاربر</label>
                                <p class="text-gray-900">${getUserTypeText(user.user_type)}</p>
                            </div>
                            ${user.company_name ? `
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">نام شرکت</label>
                                    <p class="text-gray-900">${user.company_name}</p>
                                </div>
                            ` : ''}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">تاریخ عضویت</label>
                                <p class="text-gray-900">${formatDate(user.created_at)}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex space-x-3 space-x-reverse">
                    <button onclick="closeUserDetailsModal()" 
                            class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-600 transition-all duration-200">
                        بستن
                    </button>
                    <button onclick="deleteUser(${user.user_id}); closeUserDetailsModal();" 
                            class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition-all duration-200">
                        <i class="fas fa-trash ml-1"></i>
                        حذف کاربر
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Close user details modal
function closeUserDetailsModal() {
    const modal = document.getElementById('userDetailsModal');
    if (modal) {
        modal.remove();
    }
}

// Export functions for global access
window.renderEnhancedUserResults = renderEnhancedUserResults;
window.updateEnhancedPagination = updateEnhancedPagination;
window.changeEnhancedPage = changeEnhancedPage;
window.updateResultCount = updateResultCount;
window.viewUserDetails = viewUserDetails;
window.closeUserDetailsModal = closeUserDetailsModal;
