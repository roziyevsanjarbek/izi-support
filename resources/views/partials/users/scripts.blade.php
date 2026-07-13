<script>
function userPage() {
    return {
        users: @json($usersForJs),
        startIndex: {{ $users->firstItem() ?? 1 }},
        perPage: {{ $users->perPage() }},

        modal: {
            open: false,
            mode: 'create',
        },

        showPassword: false,
        showPasswordConfirmation: false,

        deleteModal: {
            open: false,
            user: null,
        },

        selectedUser: null,
        loadingDetails: false,
        saving: false,
        deleting: false,
        errors: {},

        toast: {
            show: false,
            type: 'success',
            message: '',
            timer: null,
        },

        form: {
            id: null,
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            role_id: '',
            permissions: [],
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        },

        setupAxios() {
            const token = this.csrf();

            if (typeof axios !== 'undefined' && token) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
                axios.defaults.headers.common['Accept'] = 'application/json';
            }
        },

        notify(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;

            clearTimeout(this.toast.timer);
            this.toast.timer = setTimeout(() => {
                this.toast.show = false;
            }, 2500);
        },

        modalTitle() {
            if (this.modal.mode === 'create') return 'Create User';
            if (this.modal.mode === 'edit') return 'Edit User';
            return 'User Details';
        },

        modalSubtitle() {
            if (this.modal.mode === 'create') return 'Add a new user and assign permissions.';
            if (this.modal.mode === 'edit') return 'Update user details and permissions.';
            return 'Read-only user profile.';
        },

        clearErrors() {
            this.errors = {};
        },

        resetForm() {
            this.form = {
                id: null,
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                role_id: '',
                permissions: [],
            };
            this.showPassword = false;
            this.showPasswordConfirmation = false;
            this.clearErrors();
        },

        closeModal() {
            this.modal.open = false;
            this.selectedUser = null;
            this.loadingDetails = false;
            this.resetForm();
        },

        openCreateModal() {
            this.resetForm();
            this.modal.mode = 'create';
            this.modal.open = true;
        },

        async openModal(mode, id) {
            this.modal.mode = mode;
            this.modal.open = true;
            this.clearErrors();

            if (mode === 'create') {
                this.resetForm();
                return;
            }

            this.loadingDetails = true;
            this.selectedUser = null;

            try {
                const { data } = await axios.get(`{{ url('/users') }}/${id}`);
                const user = data.data;
                this.selectedUser = user;

                if (mode === 'edit') {
                    if (user.role_name === 'superadmin') {
                        this.closeModal();
                        this.notify('Superadmin user cannot be edited.', 'error');
                        return;
                    }

                    this.form.id = user.id;
                    this.form.name = user.name || '';
                    this.form.email = user.email || '';
                    this.form.role_id = user.role_id ? String(user.role_id) : '';
                    this.form.permissions = Array.isArray(user.permissions) ? [...user.permissions] : [];
                    this.form.password = '';
                    this.form.password_confirmation = '';
                    this.showPassword = false;
                    this.showPasswordConfirmation = false;
                }
            } catch (error) {
                console.error(error);
                this.closeModal();
                this.notify('Failed to load user data.', 'error');
            } finally {
                this.loadingDetails = false;
            }
        },

        openDeleteModal(user) {
            if (!user || user.role_name === 'superadmin') {
                this.notify('Superadmin user cannot be deleted.', 'error');
                return;
            }

            this.deleteModal.user = user;
            this.deleteModal.open = true;
        },

        closeDeleteModal() {
            this.deleteModal.open = false;
            this.deleteModal.user = null;
            this.deleting = false;
        },

        findIndexById(id) {
            return this.users.findIndex(user => Number(user.id) === Number(id));
        },

        upsertUser(user) {
            const normalized = {
                ...user,
                id: Number(user.id),
            };

            this.users = [
                normalized,
                ...this.users.filter(item => Number(item.id) !== Number(normalized.id))
            ].slice(0, this.perPage);
        },

        removeUser(id) {
            const index = this.findIndexById(id);
            if (index !== -1) {
                this.users.splice(index, 1);
            }
        },

        async saveUser() {
            this.saving = true;
            this.clearErrors();

            try {
                const payload = {
                    name: this.form.name,
                    email: this.form.email,
                    password: this.form.password,
                    password_confirmation: this.form.password_confirmation,
                    role_id: this.form.role_id,
                    permissions: this.form.permissions,
                };

                let response;

                if (this.modal.mode === 'create') {
                    response = await axios.post('{{ route('users.store') }}', payload);
                    this.upsertUser(response.data.data);
                    this.notify(response.data.message || 'User created successfully.');
                } else {
                    response = await axios.put(`{{ url('/users') }}/${this.form.id}`, payload);
                    this.upsertUser(response.data.data);
                    this.notify(response.data.message || 'User updated successfully.');
                }

                this.closeModal();
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors || {};
                    if (error.response.data.message && !error.response.data.errors) {
                        this.notify(error.response.data.message, 'error');
                    }
                } else {
                    this.notify('Something went wrong.', 'error');
                }
            } finally {
                this.saving = false;
            }
        },

        async deleteUser() {
            if (!this.deleteModal.user?.id) return;

            this.deleting = true;

            try {
                const { data } = await axios.delete(`{{ url('/users') }}/${this.deleteModal.user.id}`);
                this.removeUser(this.deleteModal.user.id);
                this.closeDeleteModal();
                this.notify(data.message || 'User deleted successfully.');
            } catch (error) {
                if (error.response?.data?.message) {
                    this.notify(error.response.data.message, 'error');
                } else {
                    this.notify('Delete failed.', 'error');
                }
            } finally {
                this.deleting = false;
            }
        },

        init() {
            this.setupAxios();

            this.$watch('modal.open', (value) => {
                document.documentElement.classList.toggle('overflow-hidden', value);
                document.body.classList.toggle('overflow-hidden', value);
            });

            this.$watch('deleteModal.open', (value) => {
                document.documentElement.classList.toggle('overflow-hidden', value);
                document.body.classList.toggle('overflow-hidden', value);
            });
        }
    }
}
</script>