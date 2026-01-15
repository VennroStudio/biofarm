function generatePagination(totalItems, itemsPerPage, offset, callback) {

    let pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const currentPage = Math.round(offset / itemsPerPage) + 1;

    let html = '';

    const startButtonsCount = 3;
    const endButtonsCount = 3;
    const leftButtonsCount = 2;
    const rightButtonsCount = 2;

    // Кнопки начала
    if (currentPage > startButtonsCount) {
        for (let i = 1; i <= startButtonsCount && i <= totalPages; i++) {
            if (i !== currentPage) {
                let newOffset = getNewOffset(itemsPerPage, i);
                html += '<div class="page-link" onclick="' + callback + '(' + newOffset + ')">' + i + '</div>';
            }
        }

        if (startButtonsCount + 1 < currentPage - leftButtonsCount && currentPage > startButtonsCount) {
            html += '<div class="page-link">...</div>';
        }
    }

    // Кнопки слева от текущей позиции
    for (let i = currentPage - leftButtonsCount; i < currentPage; i++) {
        if (i > 0 && i !== currentPage) {
            let newOffset = getNewOffset(itemsPerPage, i);
            html += '<div class="page-link" onclick="' + callback + '(' + newOffset + ')">' + i + '</div>';
        }
    }

    // Текущая позиция
    html += '<div class="page-link active">' + currentPage + '</div>';

    // Кнопки справа от текущей позиции
    for (let i = currentPage + 1; i <= currentPage + rightButtonsCount && i <= totalPages; i++) {
        if (i !== currentPage) {
            let newOffset = getNewOffset(itemsPerPage, i);
            html += '<div class="page-link" onclick="' + callback + '(' + newOffset + ')">' + i + '</div>';
        }
    }

    // Кнопки конца
    if (currentPage < totalPages - endButtonsCount) {
        if (totalPages - endButtonsCount - 1 > currentPage + rightButtonsCount && currentPage < totalPages - endButtonsCount) {
            html += '<div class="page-link">...</div>';
        }

        for (let i = totalPages - endButtonsCount + 1; i <= totalPages; i++) {
            if (i !== currentPage) {
                let newOffset = getNewOffset(itemsPerPage, i);
                html += '<div class="page-link" onclick="' + callback + '(' + newOffset + ')">' + i + '</div>';
            }
        }
    }

    pagination.innerHTML = html;
}

function getNewOffset(itemsPerPage, page) {
    return itemsPerPage * (page - 1);
}
