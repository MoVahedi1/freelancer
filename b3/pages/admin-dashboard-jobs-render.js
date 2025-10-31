// Enhanced Job Search Rendering and Pagination Functions
// Handles job display, pagination, and result management

// Render enhanced job search results
function renderEnhancedJobResults() {
    const container = document.getElementById('jobsTable');
    if (!container) return;
    
    const { currentPage, itemsPerPage } = jobSearchState.jobs.pagination;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedJobs = jobSearchState.filteredData.jobs.slice(startIndex, endIndex);
    
    if (paginatedJobs.length === 0) {
        renderJobEmptyState(container);
        return;
    }
    
    if (jobSearchState.jobs.view === 'grid') {
        renderJobsGrid(container, paginatedJobs);
    } else {
        renderJobsList(container, paginatedJobs);
    }
}

// Render jobs in grid view
function renderJobsGrid(container, jobs) {
    container.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6';
    
    container.innerHTML = jobs.map(job => `
        <div class="data-card bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
            <div class="card-header bg-gradient-to-r from-green-500/10 to-blue-500/10 p-4 rounded-t-2xl border-b border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900 truncate">${job.title || 'بدون عنوان'}</h3>
                            <span class="inline-block px-2 py-1 text-xs rounded-full ${getJobStatusClass(job.status)}">
                                ${getJobStatusText(job.status)}
                            </span>
                        </div>
                    </div>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                        #${job.job_id}
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-user ml-2 text-blue-500"></i>
                        <span class="truncate">${job.employer_name || 'کارفرمای نامشخص'}</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-money-bill-wave ml-2 text-green-500"></i>
                        <span>${formatJobBudget(job.budget_type, job.budget_min, job.budget_max)}</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-calendar ml-2 text-purple-500"></i>
                        <span>${formatDate(job.created_at)}</span>
                    </div>
                    ${job.description ? `
                        <div class="text-sm text-gray-600 mt-2">
                            <p class="line-clamp-2">${job.description.substring(0, 100)}${job.description.length > 100 ? '...' : ''}</p>
                        </div>
                    ` : ''}
                </div>
            </div>
            <div class="card-footer p-4 pt-0">
                <div class="flex space-x-2 space-x-reverse">
                    <button onclick="viewJobDetails(${job.job_id})" 
                            class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:from-green-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-eye ml-1"></i>
                        مشاهده
                    </button>
                    <button onclick="deleteJob(${job.job_id})" 
                            class="bg-gradient-to-r from-red-500 to-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:from-red-600 hover:to-red-700 transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-trash ml-1"></i>
                        حذف
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Render jobs in list view
function renderJobsList(container, jobs) {
    container.className = 'space-y-3';
    
    container.innerHTML = jobs.map(job => `
        <div class="data-card bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 space-x-reverse flex-1">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 space-x-reverse mb-1">
                                <h3 class="font-bold text-gray-900 truncate">${job.title || 'بدون عنوان'}</h3>
                                <span class="inline-block px-2 py-1 text-xs rounded-full ${getJobStatusClass(job.status)}">
                                    ${getJobStatusText(job.status)}
                                </span>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                    #${job.job_id}
                                </span>
                            </div>
                            <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-600 mb-2">
                                <span class="flex items-center">
                                    <i class="fas fa-user ml-1 text-blue-500"></i>
                                    ${job.employer_name || 'کارفرمای نامشخص'}
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-money-bill-wave ml-1 text-green-500"></i>
                                    ${formatJobBudget(job.budget_type, job.budget_min, job.budget_max)}
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-calendar ml-1 text-purple-500"></i>
                                    ${formatDate(job.created_at)}
                                </span>
                            </div>
                            ${job.description ? `
                                <div class="text-sm text-gray-600">
                                    <p class="line-clamp-1">${job.description.substring(0, 150)}${job.description.length > 150 ? '...' : ''}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        <button onclick="viewJobDetails(${job.job_id})" 
                                class="bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:from-green-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-eye ml-1"></i>
                            مشاهده
                        </button>
                        <button onclick="deleteJob(${job.job_id})" 
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

// Render empty state when no jobs found
function renderJobEmptyState(container) {
    container.className = 'text-center py-12';
    container.innerHTML = `
        <div class="max-w-md mx-auto">
            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-r from-gray-200 to-gray-300 rounded-full flex items-center justify-center">
                <i class="fas fa-briefcase text-3xl text-gray-500"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">آگهی‌ای یافت نشد</h3>
            <p class="text-gray-600 mb-6">متأسفانه هیچ آگهی‌ای با معیارهای جستجوی شما پیدا نشد.</p>
            <button onclick="clearAllJobFilters()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg font-medium hover:from-green-600 hover:to-green-700 transition-all duration-200">
                <i class="fas fa-refresh ml-2"></i>
                پاک کردن فیلترها
            </button>
        </div>
    `;
}

// Format job budget display
function formatJobBudget(budgetType, budgetMin, budgetMax) {
    if (budgetType === 'negotiable') {
        return 'قابل مذاکره';
    } else if (budgetType === 'range' && budgetMin && budgetMax) {
        return `${formatPrice(budgetMin)} - ${formatPrice(budgetMax)} تومان`;
    } else if (budgetMin) {
        return `${formatPrice(budgetMin)} تومان`;
    } else {
        return 'نامشخص';
    }
}

// Update enhanced job pagination
function updateEnhancedJobPagination() {
    const paginationContainer = document.getElementById('jobsPagination');
    if (!paginationContainer) return;
    
    const { currentPage, itemsPerPage, totalItems } = jobSearchState.jobs.pagination;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let paginationHTML = '<div class="flex items-center justify-center space-x-2 space-x-reverse">';
    
    // Previous button
    paginationHTML += `
        <button onclick="changeEnhancedJobPage(${currentPage - 1})" 
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
            <button onclick="changeEnhancedJobPage(1)" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-all duration-200">
                1
            </button>
        `;
        if (startPage > 2) {
            paginationHTML += '<span class="px-2 text-gray-500">...</span>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button onclick="changeEnhancedJobPage(${i})" 
                    class="px-3 py-2 rounded-lg border transition-all duration-200 ${
                        i === currentPage 
                            ? 'bg-green-500 text-white border-green-500' 
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
            <button onclick="changeEnhancedJobPage(${totalPages})" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-all duration-200">
                ${totalPages}
            </button>
        `;
    }
    
    // Next button
    paginationHTML += `
        <button onclick="changeEnhancedJobPage(${currentPage + 1})" 
                ${currentPage === totalPages ? 'disabled' : ''}
                class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    paginationHTML += '</div>';
    
    paginationContainer.innerHTML = paginationHTML;
}

// Change page in enhanced job pagination
function changeEnhancedJobPage(page) {
    const totalPages = Math.ceil(jobSearchState.jobs.pagination.totalItems / jobSearchState.jobs.pagination.itemsPerPage);
    
    if (page < 1 || page > totalPages) return;
    
    jobSearchState.jobs.pagination.currentPage = page;
    renderEnhancedJobResults();
    updateEnhancedJobPagination();
    
    // Scroll to top of results
    const jobsSection = document.getElementById('jobs-section');
    if (jobsSection) {
        jobsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Update job result count display
function updateJobResultCount() {
    const resultCountElement = document.getElementById('jobResultCount');
    if (resultCountElement) {
        const { totalItems } = jobSearchState.jobs.pagination;
        const { currentPage, itemsPerPage } = jobSearchState.jobs.pagination;
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);
        
        if (totalItems === 0) {
            resultCountElement.textContent = 'هیچ آگهی‌ای یافت نشد';
        } else {
            resultCountElement.textContent = `نمایش ${startItem} تا ${endItem} از ${totalItems} آگهی`;
        }
    }
}

// View job details modal
function viewJobDetails(jobId) {
    const job = jobSearchState.originalData.jobs.find(j => j.job_id == jobId);
    if (!job) {
        console.error('Job not found:', jobId);
        return;
    }
    
    // Create and show job details modal
    const modalHTML = `
        <div id="jobDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-900">جزئیات آگهی</h3>
                        <button onclick="closeJobDetailsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center space-x-4 space-x-reverse mb-6">
                        <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-2xl">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">${job.title || 'بدون عنوان'}</h2>
                            <span class="inline-block px-3 py-1 text-sm rounded-full ${getJobStatusClass(job.status)}">
                                ${getJobStatusText(job.status)}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">شناسه آگهی</label>
                                <p class="text-gray-900">#${job.job_id}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">کارفرما</label>
                                <p class="text-gray-900">${job.employer_name || 'نامشخص'}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">بودجه</label>
                                <p class="text-gray-900">${formatJobBudget(job.budget_type, job.budget_min, job.budget_max)}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">وضعیت</label>
                                <p class="text-gray-900">${getJobStatusText(job.status)}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">تاریخ ایجاد</label>
                                <p class="text-gray-900">${formatDate(job.created_at)}</p>
                            </div>
                        </div>
                    </div>
                    
                    ${job.description ? `
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">توضیحات</label>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-900 whitespace-pre-wrap">${job.description}</p>
                            </div>
                        </div>
                    ` : ''}
                </div>
                <div class="p-6 border-t border-gray-200 flex space-x-3 space-x-reverse">
                    <button onclick="closeJobDetailsModal()" 
                            class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-600 transition-all duration-200">
                        بستن
                    </button>
                    <button onclick="deleteJob(${job.job_id}); closeJobDetailsModal();" 
                            class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition-all duration-200">
                        <i class="fas fa-trash ml-1"></i>
                        حذف آگهی
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Close job details modal
function closeJobDetailsModal() {
    const modal = document.getElementById('jobDetailsModal');
    if (modal) {
        modal.remove();
    }
}

// Export functions for global access
window.renderEnhancedJobResults = renderEnhancedJobResults;
window.updateEnhancedJobPagination = updateEnhancedJobPagination;
window.changeEnhancedJobPage = changeEnhancedJobPage;
window.updateJobResultCount = updateJobResultCount;
window.viewJobDetails = viewJobDetails;
window.closeJobDetailsModal = closeJobDetailsModal;
window.formatJobBudget = formatJobBudget;

console.log('💼 Job Rendering System Loaded');
