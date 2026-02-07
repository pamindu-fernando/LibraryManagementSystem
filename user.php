<?php
include 'db_config.php';

// Fetch all books from the database for the user view
$result = mysqli_query($conn, "SELECT * FROM books ORDER BY id DESC");
$db_books = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['id'] = (int)$row['id'];

    $sizeInBytes = 0;
    if (!empty($row['file_path']) && file_exists($row['file_path'])) {
        $sizeInBytes = filesize($row['file_path']);
    }
    $row['size_bytes'] = $sizeInBytes;

    $db_books[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Library - Browse Books</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50/50 min-h-screen text-slate-950" x-data="dashboard()">

    <header class="sticky top-0 z-50 border-b  bg-green-600/80 backdrop-blur-md text-white">
        <div class="container mx-auto flex h-16 items-center justify-between px-4">
            <div class="flex items-center gap-3">
                <img src="logo.png" alt="Library Logo" class="h-10 w-auto rounded-md object-contain brightness-0 invert">
                <div>
                    <h1 class="text-lg font-semibold leading-tight">Student Library</h1>
                    <p class="text-xs text-green-100">Browse & Download Resources</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white text-green-700 px-2 py-0.5 rounded-md text-xs font-medium">Student</span>

                <button @click="logout" class="p-2 hover:bg-green-500 rounded-md text-white transition-colors" title="Logout">
                    <i data-lucide="log-out" class="size-5"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h3 class="text-sm font-medium text-gray-500">Total Books Available</h3>
                <div class="text-2xl font-bold" x-text="books.length"></div>
            </div>
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between pb-2">
                    <h3 class="text-sm font-medium text-gray-500">Repository Size</h3>
                    <i data-lucide="database" class="size-4 text-gray-400"></i>
                </div>
                <div class="text-2xl font-bold text-green-600" x-text="stats.totalMB + ' MB'"></div>
            </div>
        </div>

        <div class="rounded-xl border bg-white shadow-sm overflow-hidden">
            <div class="p-6 border-b flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-bold">Library Catalog</h2>
                <div class="flex gap-2">
                    <input type="text" x-model="searchTerm" placeholder="Search title or category..." class="border rounded-md px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-green-500 w-full sm:w-80">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b text-gray-600 font-semibold">
                        <tr>
                            <th class="px-6 py-4">Book Title</th>
                            <th class="px-6 py-4">Author</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4 text-center">Size</th>
                            <th class="px-6 py-4 text-right">Download</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="book in filteredBooks" :key="book.id">
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900" x-text="book.title"></td>
                                <td class="px-6 py-4 text-gray-600" x-text="book.author || '---'"></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-md border border-gray-200 text-xs bg-gray-50 text-gray-600" x-text="book.category || 'General'"></span>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-500" x-text="(book.size_bytes / (1024*1024)).toFixed(2) + ' MB'"></td>
                                <td class="px-6 py-4 text-right">
                                    <template x-if="book.file_path">
                                        <a :href="book.file_path" download class="inline-flex items-center gap-2 text-green-600 hover:text-green-700 font-medium transition-colors">
                                            <i data-lucide="download" class="size-5"></i>
                                            <span>Get PDF</span>
                                        </a>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function dashboard() {
            return {
                searchTerm: '',
                books: <?php echo json_encode($db_books); ?>,

                init() {
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });
                    this.$watch('searchTerm', () => {
                        this.$nextTick(() => lucide.createIcons());
                    });
                },

                get stats() {
                    const totalBytes = this.books.reduce((s, b) => s + (parseInt(b.size_bytes) || 0), 0);
                    return {
                        totalMB: (totalBytes / (1024 * 1024)).toFixed(2)
                    };
                },

                get filteredBooks() {
                    const q = this.searchTerm.toLowerCase();
                    return this.books.filter(b =>
                        b.title.toLowerCase().includes(q) || (b.category && b.category.toLowerCase().includes(q))
                    );
                },

                // Redirect to login page or home
                // Inside your dashboard() function in main.php
                logout() {
                    // Optional: Confirm with the user before leaving
                    if (confirm("Are you sure you want to log out of the Admin panel?")) {
                        window.location.href = 'http://127.0.0.1:5500/login.html';
                    }
                }
            }
        }
    </script>
</body>

</html>