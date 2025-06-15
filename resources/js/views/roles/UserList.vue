<script setup>
import AddNewUserDrawer from '@/views/user/list/AddNewUserDrawer.vue'; // Ensure path is correct
import axios from 'axios'; // Or your global $api helper
import { onMounted, ref, watch } from 'vue';

// Refs for filters and pagination
const searchQuery = ref('');
const selectedRole = ref('');
const itemsPerPage = ref(10);
const page = ref(1);
const sortBy = ref([]); 
// const orderBy = ref([]);

const users = ref([]);
const totalUsers = ref(0);
const isLoading = ref(true);
const selectedRows = ref([]); 
// const updateOptions = options => {
//   sortBy.value = options.sortBy[0]?.key
//   orderBy.value = options.sortBy[0]?.order
// }

// Headers
const headers = [
  {
    title: 'User',
    key: 'user',
  },
  {
    title: 'Role',
    key: 'role',
  },
  {
    title: 'Status',
    key: 'status',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

const fetchUsers = async () => {
  isLoading.value = true;
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      q: searchQuery.value || undefined, // Send undefined if empty to be cleaner
      role: selectedRole.value || undefined, // Send actual role name string
      sortBy: sortBy.value.length ? sortBy.value[0].key : undefined,
      orderBy: sortBy.value.length ? sortBy.value[0].order : undefined, // 'asc' or 'desc'
    };

    // Clean up undefined params before sending
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key]);

    const response = await axios.get('/api/users', { params });

    users.value = response.data.users.map(userFromApi => ({
      id: userFromApi.id,
      fullName: userFromApi.name, 
      email: userFromApi.email,
      // API returns roles as an array of objects. Take the first role name.
      role: userFromApi.roles && userFromApi.roles.length > 0 ? userFromApi.roles[0].name : 'N/A',
      avatar: userFromApi.avatar_url || null, // You'll need to add avatar_url to your User API Resource or model accessor
      status: 'active', 
      // currentPlan: 'Basic', // Static plan for now if template needs it
      // billing: 'Auto Debit', // Static billing for now if template needs it
    }));
    totalUsers.value = response.data.totalUsers;

  } catch (error) {
    console.error('Error fetching users:', error);
    users.value = [];
    totalUsers.value = 0;
  } finally {
    isLoading.value = false;
  }
};

// VDataTableServer's @update:options handler
const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
  sortBy.value = options.sortBy || []; // Ensure sortBy is always an array
  // Watchers will trigger fetchUsers
};

// Watchers for filters and pagination
watch([searchQuery, selectedRole], () => {
  page.value = 1; // Reset to first page when filters change
  fetchUsers();
});
// No need to watch page, itemsPerPage, sortBy directly if updateOptions handles them
// and they are part of the initial object passed to VDataTableServer

// Initial fetch
onMounted(fetchUsers);


//const users = computed(() => usersData.value.users)
//const totalUsers = computed(() => usersData.value.totalUsers)

// 👉 search filters
const roleFilterOptions = ref([ 
  { title: 'All', value: '' }, 
  { title: 'Super Admin', value: 'Super Admin' },
  { title: 'Faculty', value: 'Faculty' },
  { title: 'Rector', value: 'Rector' },
  { title: 'Director', value: 'Director' },
  { title: 'HOD', value: 'HOD' },
]);

const resolveUserRoleVariant = role => {
  if (!role) return { color: 'grey', icon: 'tabler-user' };
  const roleLowerCase = role.toLowerCase();
  if (roleLowerCase === 'faculty') return { color: 'primary', icon: 'tabler-user' };
  if (roleLowerCase === 'rector') return { color: 'warning', icon: 'tabler-settings' };
  if (roleLowerCase === 'director') return { color: 'success', icon: 'tabler-chart-donut' };
  if (roleLowerCase === 'hod') return { color: 'info', icon: 'tabler-pencil' };
  if (roleLowerCase === 'super admin') return { color: 'error', icon: 'tabler-device-laptop' };
  return { color: 'primary', icon: 'tabler-user' };
};

const resolveUserStatusVariant = stat => {
  // const statLowerCase = stat.toLowerCase(); // Not needed if static
  // For now, let's assume all are 'active'
  if (stat === 'active') return 'success';
  return 'secondary'; // Default for any other static status you might add
};

const isAddNewUserDrawerVisible = ref(false)

const addNewUser = async (userDataFromDrawer) => {
  try {
    const payload = {
      fullName: userDataFromDrawer.fullName, 
      email: userDataFromDrawer.email,
      password: userDataFromDrawer.password, 
      role: userDataFromDrawer.role, 
    };
    await axios.post('/api/users', payload);
    fetchUsers(); // Refresh 
    isAddNewUserDrawerVisible.value = false;
  } catch (error) {
    console.error('Error adding user:', error.response?.data || error.message);
    // Handle validation errors: error.response.data.errors
    alert(error.response?.data?.message || 'Failed to add user.');
  }
};

const deleteUser = async (userId) => {
  if (confirm('Are you sure you want to delete this user?')) {
    try {
      await axios.delete(`/api/users/${userId}`);
      fetchUsers(); // Refresh list
    } catch (error) {
      console.error('Error deleting user:', error.response?.data || error.message);
      alert(error.response?.data?.message || 'Failed to delete user.');
    }
  }
};
</script>

<template>
  <section>
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="d-flex gap-2 align-center">
          <p class="text-body-1 mb-0">
            Show
          </p>
          <AppSelect
            :model-value="itemsPerPage"
            :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
              { value: -1, title: 'All' },
            ]"
            style="inline-size: 5.5rem;"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />
        </div>

        <VSpacer />

        <div class="d-flex align-center flex-wrap gap-4">
          <!-- 👉 Search  -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search User"
            style="inline-size: 15.625rem;"
          />

          <!-- 👉 Add user button -->
          <AppSelect
            v-model="selectedRole"
            placeholder="Select Role"
            :items="roleFilterOptions"
            clearable
            clear-icon="tabler-x"
            style="inline-size: 10rem;"
          />
        </div>
        <VBtn
            prepend-icon="tabler-plus"
            @click="isAddNewUserDrawerVisible = true"
          >
            Add New User
          </VBtn>
      </VCardText>

      <VDivider />

      <!-- SECTION datatable -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :items-per-page-options="[
          { value: 10, title: '10' },
          { value: 20, title: '20' },
          { value: 50, title: '50' },
          { value: -1, title: '$vuetify.dataFooter.itemsPerPageAll' },
        ]"
        :items="users"
        :items-length="totalUsers"
        :headers="headers"
        class="text-no-wrap"
        show-select
        @update:options="updateOptions"
      >
        <!-- User -->
        <template #item.user="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar
              size="34"
              :variant="!item.avatar ? 'tonal' : undefined"
              :color="!item.avatar ? resolveUserRoleVariant(item.role).color : undefined"
            >
              <VImg
                v-if="item.avatar"
                :src="item.avatar"
              />
              <span v-else>{{ avatarText(item.fullName) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: '', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.fullName }}
                </RouterLink>
              </h6>
              <div class="text-sm">
                {{ item.email }}
              </div>
            </div>
          </div>
        </template>

        <!-- 👉 Role -->
        <template #item.role="{ item }">
          <div class="d-flex align-center gap-x-2">
            <VIcon
              :size="22"
              :icon="resolveUserRoleVariant(item.role).icon"
              :color="resolveUserRoleVariant(item.role).color"
            />

            <div class="text-capitalize text-high-emphasis text-body-1">
              {{ item.role }}
            </div>
          </div>
        </template>

        <!-- Plan -->
        <template #item.plan="{ item }">
          <div class="text-body-1 text-high-emphasis text-capitalize">
            {{ item.currentPlan }}
          </div>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="resolveUserStatusVariant(item.status)"
            size="small"
            label
            class="text-capitalize"
          >
            {{ item.status }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn @click="deleteUser(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>

          <IconBtn>
            <VIcon icon="tabler-eye" />
          </IconBtn>

          <VBtn
            icon
            variant="text"
            color="medium-emphasis"
          >
            <VIcon icon="tabler-dots-vertical" />
            <VMenu activator="parent">
              <VList>
                <VListItem link>
                  <template #prepend>
                    <VIcon icon="tabler-pencil" />
                  </template>
                  <VListItemTitle>Edit</VListItemTitle>
                </VListItem>

                <VListItem @click="deleteUser(item.id)">
                  <template #prepend>
                    <VIcon icon="tabler-trash" />
                  </template>
                  <VListItemTitle>Delete</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalUsers"
          />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>

    <!-- 👉 Add New User -->
    <AddNewUserDrawer
      v-model:is-drawer-open="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
    />
  </section>
</template>

<style lang="scss">
.text-capitalize {
  text-transform: capitalize;
}

.user-list-name:not(:hover) {
  color: rgba(var(--v-theme-on-background), var(--v-medium-emphasis-opacity));
}
</style>
