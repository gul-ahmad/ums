<script setup>

import avatar1 from '@images/avatars/avatar-1.png';
import avatar2 from '@images/avatars/avatar-2.png';
import avatar3 from '@images/avatars/avatar-3.png';
import avatar4 from '@images/avatars/avatar-4.png';
import girlUsingMobile from '@images/pages/girl-using-mobile.png';

import axios from 'axios';
import { onMounted, ref } from 'vue';

//import AddEditRoleDialog from '@/views/apps/roles/AddEditRoleDialog.vue';


const displayAvatars = [avatar1, avatar2, avatar3, avatar4];

const rolesFromApi = ref([]);
const isLoading = ref(true);
const isRoleDialogVisible = ref(false);
const roleDetailForDialog = ref(null);
const isAddRoleDialogVisible = ref(false);
const allPermissionsForDialog = ref([]);

const fetchRoles = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get('/api/roles');
    rolesFromApi.value = response.data; 
  } catch (error) {
    console.error('Failed to fetch roles:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchAllPermissions = async () => {
  try {
    const response = await axios.get('/api/permissions');
    allPermissionsForDialog.value = response.data;
  } catch (error) {
    console.error('Failed to fetch permissions:', error);
  }
};

onMounted(() => {
  fetchRoles();
  fetchAllPermissions();
});

const openEditRoleDialog = async (roleFromCard) => { 
  try {
    isLoading.value = true;
    const response = await axios.get(`/api/roles/${roleFromCard.id}`);
    roleDetailForDialog.value = response.data;
    isRoleDialogVisible.value = true;
  } catch (error)
  {
    console.error(`Failed to fetch details for role ${roleFromCard.role}:`, error);
  } finally {
    isLoading.value = false;
  }
};

const openAddRoleDialog = () => {
  roleDetailForDialog.value = null;
  isAddRoleDialogVisible.value = true;
};

const handleRoleSaved = async () => {
  isAddRoleDialogVisible.value = false;
  isRoleDialogVisible.value = false;
  await fetchRoles();
};

const deleteRole = async (roleId) => {
  if (confirm('Are you sure you want to delete this role?')) {
    try {
      await axios.delete(`/api/roles/${roleId}`);
      await fetchRoles();
    } catch (error) {
      alert(error.response?.data?.message || 'Failed to delete role.');
    }
  }
};

const copyRole = (role) => {
  alert(`Copying role: ${role.role}`);
};
</script>

<template>
  <VRow v-if="!isLoading">
    <VCol
      v-for="(item, roleIndex) in rolesFromApi"
      :key="item.id"
      cols="12"
      sm="6"
      lg="4"
    >
      <VCard>
        <VCardText class="d-flex align-center pb-4">
          <div class="text-body-1">
            Total {{ item.users_count }} users
          </div>

          <VSpacer />

          <!-- {/* Frontend handles display of some static avatars based on count */} -->
          <div class="v-avatar-group">
            <template
              v-for="i in Math.min(item.users_count, 3)" 
              :key="`avatar-role-${item.id}-${i}`"
            >
              <VAvatar
                size="40"
                :image="displayAvatars[(roleIndex + i - 1) % displayAvatars.length]" 
              />
            </template>
            <VAvatar
              v-if="item.users_count > (displayAvatars.length > 3 ? 3 : 4)"
              :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
            >
              <span>
                +{{ item.users_count - (displayAvatars.length > 3 ? 3 : 4) }}
              </span>
            </VAvatar>
          </div>
        </VCardText>

        <VCardText>
          <div class="d-flex justify-space-between align-center">
            <div>
              <h5 class="text-h5">
                {{ item.role }}
              </h5>
              <div class="d-flex align-center">
                <a
                  href="javascript:void(0)"
                  @click="openEditRoleDialog(item)"
                >
                  Edit Role
                </a>
              </div>
            </div>
            <IconBtn @click="copyRole(item)">
              <VIcon icon="tabler-copy" class="text-high-emphasis" />
            </IconBtn>
            <IconBtn @click="deleteRole(item.id)" color="error" class="ms-2">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </VCardText>
      </VCard>
    </VCol>

   
    <VCol cols="12" sm="6" lg="4">
      <VCard class="h-100" :ripple="false" @click="openAddRoleDialog" style="cursor: pointer;">
        <VRow no-gutters class="h-100">
          <VCol cols="5" class="d-flex flex-column justify-end align-center mt-5">
            <img width="85" :src="girlUsingMobile" />
          </VCol>
          <VCol cols="7">
            <VCardText class="d-flex flex-column align-end justify-end gap-4 fill-height">
              <VBtn size="small">Add New Role</VBtn>
              <div class="text-end">Add new role,<br> if it doesn't exist.</div>
            </VCardText>
          </VCol>
        </VRow>
      </VCard>
    </VCol>
  </VRow>
  <VRow v-else class="justify-center align-center" style="min-block-size: 200px;">
    <VProgressCircular indeterminate color="primary" />
  </VRow>

  <!-- <AddEditRoleDialog
    v-if="isAddRoleDialogVisible || isRoleDialogVisible"
    :is-dialog-visible="isAddRoleDialogVisible || isRoleDialogVisible"
    :role-data="roleDetailForDialog"
    :all-permissions="allPermissionsForDialog"
    @update:is-dialog-visible="val => { isAddRoleDialogVisible = val; isRoleDialogVisible = val; if (!val) roleDetailForDialog = null; }"
    @save="handleRoleSaved"
  /> -->
</template>
