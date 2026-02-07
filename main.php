<?php
include 'db_config.php';

// Fetch all books from the database
$result = mysqli_query($conn, "SELECT * FROM books ORDER BY id DESC");
$db_books = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['id'] = (int)$row['id'];
    $row['quantity'] = (int)$row['quantity'];
    $row['available'] = (int)$row['available'];

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
    <title>Library Management Dashboard</title>
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
                    <h1 class="text-lg font-semibold leading-tight">Library Management</h1>
                    <p class="text-xs text-green-100">Campus Project Dashboard</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white text-green-700 px-2 py-0.5 rounded-md text-xs font-medium">Admin</span>
                <button @click="logout" class="p-2 hover:bg-green-500 rounded-md text-white transition-colors">
                    <i data-lucide="log-out" class="size-5"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h3 class="text-sm font-medium text-gray-500">Total Titles</h3>
                <div class="text-2xl font-bold" x-text="books.length"></div>
            </div>
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h3 class="text-sm font-medium text-gray-500">Available Copies</h3>
                <div class="text-2xl font-bold text-green-600" x-text="stats.available"></div>
            </div>
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between pb-2">
                    <h3 class="text-sm font-medium text-gray-500">Storage Used</h3>
                    <i data-lucide="database" class="size-4 text-gray-400"></i>
                </div>
                <div class="text-2xl font-bold text-blue-600" x-text="stats.totalMB + ' MB'"></div>
            </div>
        </div>

        <div class="rounded-xl border bg-white shadow-sm overflow-hidden">
            <div class="p-6 border-b flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-bold">Book Inventory</h2>
                <div class="flex gap-2">
                    <input type="text" x-model="searchTerm" placeholder="Search title or category..." class="border rounded-md px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    <button @click="handleAddNew" class="bg-black text-white px-4 py-2 rounded-md text-sm flex items-center gap-2 transition-colors hover:bg-gray-800">
                        <i data-lucide="plus" class="size-4"></i> Add Book
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b text-gray-600 font-semibold">
                        <tr>
                            <th class="px-6 py-4">Title</th>
                            <th class="px-6 py-4">Author</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4 text-center">Size</th>
                            <th class="px-6 py-4 text-right">Actions</th>
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
                                    <div class="flex justify-end gap-4">
                                        <template x-if="book.file_path">
                                            <a :href="book.file_path" download class="text-gray-400 hover:text-blue-600 transition-colors">
                                                <i data-lucide="download" class="size-5"></i>
                                            </a>
                                        </template>
                                        <button @click="handleDeleteClick(book)" class="text-gray-400 hover:text-red-600 transition-colors">
                                            <i data-lucide="trash-2" class="size-5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div x-show="dialogOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div @click.away="dialogOpen = false" class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
            <h3 class="text-xl font-bold mb-4">Upload New Book</h3>
            <form @submit.prevent="handleSaveBook" class="space-y-4">
                <input type="text" x-model="editingBook.title" placeholder="Book Title" required class="w-full border rounded-md p-2 text-sm outline-none">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" x-model="editingBook.author" placeholder="Author" class="w-full border rounded-md p-2 text-sm outline-none">
                    <input type="text" x-model="editingBook.category" placeholder="Category" class="w-full border rounded-md p-2 text-sm outline-none">
                </div>
                <input type="file" accept="application/pdf" @change="editingBook.pdfFile = $event.target.files[0]" class="w-full border rounded-md p-2 text-sm">
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="dialogOpen = false" class="px-4 py-2 text-sm font-medium">Cancel</button>
                    <button type="submit" :disabled="isSubmitting" class="bg-blue-600 text-white px-6 py-2 rounded-md text-sm font-medium" x-text="isSubmitting ? 'Uploading...' : 'Save Book'"></button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="deleteDialogOpen" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl">
            <h3 class="text-lg font-bold">Permanently delete?</h3>
            <p class="text-sm text-gray-500 mt-2">Remove "<span x-text="bookToDelete?.title"></span>"?</p>
            <div class="flex justify-end gap-3 mt-6">
                <button @click="deleteDialogOpen = false" class="px-4 py-2 border rounded-md text-sm">Cancel</button>
                <button @click="confirmDelete" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script>
        function dashboard() {
            return {
                searchTerm: '',
                dialogOpen: false,
                deleteDialogOpen: false,
                editingBook: {},
                bookToDelete: null,
                isSubmitting: false,
                books: <?php echo json_encode($db_books); ?>,

                init() {
                    // This runs as soon as Alpine.js is ready
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });

                    // Watch for search changes to re-render icons in filtered results
                    this.$watch('searchTerm', () => {
                        this.$nextTick(() => lucide.createIcons());
                    });
                },

                get stats() {
                    const available = this.books.reduce((s, b) => s + (parseInt(b.available) || 0), 0);
                    const totalBytes = this.books.reduce((s, b) => s + (parseInt(b.size_bytes) || 0), 0);
                    return {
                        available,
                        totalMB: (totalBytes / (1024 * 1024)).toFixed(2)
                    };
                },

                get filteredBooks() {
                    const q = this.searchTerm.toLowerCase();
                    return this.books.filter(b =>
                        b.title.toLowerCase().includes(q) || (b.category && b.category.toLowerCase().includes(q))
                    );
                },

                handleAddNew() {
                    this.editingBook = {
                        title: '',
                        author: '',
                        category: ''
                    };
                    this.dialogOpen = true;
                },

                handleSaveBook() {
                    this.isSubmitting = true;
                    const formData = new FormData();
                    formData.append('title', this.editingBook.title);
                    formData.append('author', this.editingBook.author || '');
                    formData.append('category', this.editingBook.category || '');
                    if (this.editingBook.pdfFile) formData.append('pdfFile', this.editingBook.pdfFile);

                    fetch('add_book.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.books.unshift({
                                    id: data.bookId,
                                    title: this.editingBook.title,
                                    author: this.editingBook.author,
                                    category: this.editingBook.category,
                                    file_path: data.data.pdfFile,
                                    size_bytes: data.data.size_bytes,
                                    available: 1,
                                    quantity: 1
                                });
                                this.dialogOpen = false;
                                this.$nextTick(() => lucide.createIcons()); // Re-render icons for new row
                            }
                            this.isSubmitting = false;
                        });
                },

                handleDeleteClick(book) {
                    this.bookToDelete = book;
                    this.deleteDialogOpen = true;
                },
                confirmDelete() {
                    fetch('delete_book.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: this.bookToDelete.id
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.books = this.books.filter(b => b.id !== this.bookToDelete.id);
                                this.deleteDialogOpen = false;
                            }
                        });
                },
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