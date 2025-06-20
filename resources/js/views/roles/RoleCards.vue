<script setup>

import { $api } from '@/utils/api';
import avatar1 from '@images/avatars/avatar-1.png';
import avatar2 from '@images/avatars/avatar-2.png';
import avatar3 from '@images/avatars/avatar-3.png';
import avatar4 from '@images/avatars/avatar-4.png';
import girlUsingMobile from '@images/pages/girl-using-mobile.png';

import axios from 'axios';
import { onMounted, ref } from 'vue';

import AddEditRoleDialog from '@/components/dialogs/AddEditRoleDialog.vue';


const displayAvatars = [avatar1, avatar2, avatar3, avatar4];

const rolesFromApi = ref([]);
const isLoading = ref(true);
const isRoleDialogVisible = ref(false);
const roleDetailForDialog = ref(null);
const isDialogVisible = ref(false);
const isAddRoleDialogVisible = ref(false);
const allPermissionsForDialog = ref([]);

const isDialogActive = ref(false); // Single flag to control dialog visibility
const currentEditingRole = ref(null); // Holds role data for editing, null for adding

const successMessage = ref(''); // For success notifications
const errorMessage = ref('');   // For error notifications

const closeDialog = () => {
  isDialogVisible.value = false;
  roleDetailForDialog.value = null; // Important to reset
}

const fetchRoles = async () => {
  isLoading.value = true;
  try {
    //const response = await axios.get('/api/roles');
    //alert('Fetching roles');
    const response = await $api('/roles');
   // console.log('roles:', response.data);
    rolesFromApi.value = response.data; 
  } catch (error) {
    console.error('Failed to fetch roles:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchAllPermissions = async () => {
  try {
    //const response = await axios.get('/api/permissions');
    const response = await $api('/permissions');
    console.log('permissions:', response);
    allPermissionsForDialog.value = response;
  } catch (error) {
    console.error('Failed to fetch permissions:', error);
  }
};

onMounted(() => {
  fetchRoles();
  fetchAllPermissions();
});

const openEditDialog = async (roleToEdit) => {
  try {
    isLoading.value = true;
    const response = await $api(`/roles/${roleToEdit.id}`);
    currentEditingRole.value = response; // API returns { id, name, assigned_permission_ids }
    isDialogActive.value = true;       // Open the dialog
  } catch (error) {
    console.error(`Failed to fetch details for role ${roleToEdit.role}:`, error.data || error);
    errorMessage.value = error.data?.message || 'Failed to load role details.';
  } finally {
    isLoading.value = false;
  }
};

const openAddDialog = () => {
  currentEditingRole.value = null; // Explicitly set to null for "add" mode
  isDialogActive.value = true;    // Open the dialog
};

const handleDialogClose = () => { // Called by dialog's @update:is-dialog-visible
  isDialogActive.value = false;
  currentEditingRole.value = null; // Always reset editing role when dialog closes
};

// const handleRoleSaved = async () => {
//   isAddRoleDialogVisible.value = false;
//   isRoleDialogVisible.value = false;
//   //await fetchRoles();
// };
const handleRoleSavedFromDialog = async (formDataFromDialog) => {
  isLoading.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    let apiResponse;
    if (formDataFromDialog.id) { // Editing
      apiResponse = await $api(`/roles/${formDataFromDialog.id}`, {
        method: 'PUT',
        body: formDataFromDialog,
      });
      successMessage.value = apiResponse.message || 'Role updated successfully!';
    } else { // Adding
      apiResponse = await $api('/roles', {
        method: 'POST',
        body: formDataFromDialog,
      });
      successMessage.value = apiResponse.message || 'Role added successfully!';
    }

    handleDialogClose(); // Close dialog and reset state
    await fetchRoles();  // Refresh list

    setTimeout(() => { successMessage.value = ''; }, 3000);
  } catch (error) {
    // ... your existing error handling ...
    console.error('Failed to save role:', error.data || error.response?.data || error);
    if (error.data && error.data.errors) {
      const validationErrors = Object.values(error.data.errors).flat().join('\n');
      errorMessage.value = `Validation Failed:\n${validationErrors}`;
    } else {
      errorMessage.value = error.data?.message || error.response?.data?.message || 'An unexpected error occurred.';
    }
  } finally {
    isLoading.value = false;
  }
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

<VAlert
    v-if="successMessage"
    type="success"
    closable
    class="mb-4"
    @click:close="successMessage = ''"
  >
    {{ successMessage }}
  </VAlert>
  <VAlert
    v-if="errorMessage"
    type="error"
    closable
    class="mb-4"
    @click:close="errorMessage = ''"
  >
    <pre style="white-space: pre-wrap;">{{ errorMessage }}</pre> 
  </VAlert>
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
                  @click="openEditDialog(item)"
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
      <VCard class="h-100" :ripple="false" @click="openAddDialog" style="cursor: pointer;">
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

  <AddEditRoleDialog
    v-if="isDialogActive" 
    :is-dialog-visible="isDialogActive" 
    :role-data="currentEditingRole" 
    :all-permissions="allPermissionsForDialog"
    @update:is-dialog-visible="handleDialogClose"
    @save="handleRoleSavedFromDialog"
  />
</template>
