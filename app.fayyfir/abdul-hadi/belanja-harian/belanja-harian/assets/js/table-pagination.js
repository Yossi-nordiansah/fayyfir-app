/**
 * Universal Table Pagination & Search
 * 
 * Cara pakai:
 * initTablePagination({
 *   tableId: "materialTable",
 *   rowsPerPageId: "rowsPerPage",
 *   searchInputId: "searchInput",
 *   paginationId: "paginationControls",
 *   infoId: "totalRowsInfo"
 * });
 */

function initTablePagination(config) {
    const table = document.getElementById(config.tableId);
    if (!table) return;

    const rowsPerPage = document.getElementById(config.rowsPerPageId);
    const searchInput = document.getElementById(config.searchInputId);
    const pagination = document.getElementById(config.paginationId);
    const totalInfo = document.getElementById(config.infoId);

    const rows = table.getElementsByClassName("data-row");
    let currentPage = 1;

    // Default: semua baris match
    for (let row of rows) row.classList.add("match");

    function filterRows() {
        const q = searchInput.value.toLowerCase();

        for (let row of rows) {
            const text = row.innerText.toLowerCase();

            if (text.includes(q)) {
                row.classList.add("match");
            } else {
                row.classList.remove("match");
                row.style.display = "none";
            }
        }
        currentPage = 1;
        paginate();
    }

    function paginate() {
        const maxRows = parseInt(rowsPerPage.value) || 10;
        const visibleRows = [...rows].filter(r => r.classList.contains("match"));
        const totalPages = Math.ceil(visibleRows.length / maxRows);

        currentPage = Math.min(currentPage, totalPages || 1);

        visibleRows.forEach((row, idx) => {
            row.style.display = (idx >= (currentPage - 1) * maxRows && idx < currentPage * maxRows) 
                ? "" 
                : "none";
        });

        // Build pagination
        pagination.innerHTML = "";

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.className =
                "px-2 py-1 border rounded " +
                (i === currentPage ? "bg-yellow-500 text-white" : "hover:bg-yellow-100");
            btn.textContent = i;
            btn.onclick = () => {
                currentPage = i;
                paginate();
            };
            pagination.appendChild(btn);
        }

        if (totalInfo) {
            totalInfo.textContent = `Menampilkan ${visibleRows.length} data dari total ${rows.length}`;
        }

        // Callback jika ada pembaruan (misal untuk grand total)
        if (typeof config.onUpdate === "function") {
            config.onUpdate(visibleRows);
        }
    }

    // Bind events
    rowsPerPage?.addEventListener("change", paginate);
    searchInput?.addEventListener("keyup", filterRows);

    // Initialize
    paginate();
}