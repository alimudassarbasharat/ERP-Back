<template>
  <div class="exam-container">
    <div class="header-section">
      <h2 class="text-2xl font-bold text-gray-800">Exams</h2>
      <el-button type="primary" @click="openCreateModal" class="create-btn">
        <el-icon><Plus /></el-icon>
        Create Exam
      </el-button>
    </div>

    <!-- Exam List Table -->
    <el-table
      v-loading="loading"
      :data="exams"
      style="width: 100%"
      class="mt-4"
    >
      <el-table-column prop="name" label="Exam Name" />
      <el-table-column prop="term" label="Term" />
      <el-table-column prop="academic_year" label="Academic Year" />
      <el-table-column label="Actions" width="200">
        <template #default="{ row }">
          <el-button-group>
            <el-button type="primary" @click="openEditModal(row)" size="small">
              <el-icon><Edit /></el-icon>
            </el-button>
            <el-button type="danger" @click="confirmDelete(row)" size="small">
              <el-icon><Delete /></el-icon>
            </el-button>
          </el-button-group>
        </template>
      </el-table-column>
    </el-table>

    <!-- Create/Edit Modal -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEditing ? 'Edit Exam' : 'Create Exam'"
      width="500px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
        class="exam-form"
      >
        <el-form-item label="Exam Name" prop="name">
          <el-input v-model="form.name" placeholder="Enter exam name" />
        </el-form-item>
        <el-form-item label="Term" prop="term">
          <el-input v-model="form.term" placeholder="Enter term" />
        </el-form-item>
        <el-form-item label="Academic Year" prop="academic_year">
          <el-input v-model="form.academic_year" placeholder="Enter academic year" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="submitForm" :loading="submitting">
            {{ isEditing ? 'Update' : 'Create' }}
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Edit, Delete } from '@element-plus/icons-vue'
import axios from 'axios'

const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const isEditing = ref(false)
const exams = ref([])
const formRef = ref(null)

const form = ref({
  name: '',
  term: '',
  academic_year: '',
  merchant_id: null,
  created_by: null
})

const rules = {
  name: [{ required: true, message: 'Please enter exam name', trigger: 'blur' }],
  term: [{ required: true, message: 'Please enter term', trigger: 'blur' }],
  academic_year: [{ required: true, message: 'Please enter academic year', trigger: 'blur' }]
}

const fetchExams = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/exams')
    exams.value = response.data.result
  } catch (error) {
    ElMessage.error('Failed to fetch exams')
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    name: '',
    term: '',
    academic_year: '',
    merchant_id: null,
    created_by: null
  }
  dialogVisible.value = true
}

const openEditModal = (exam) => {
  isEditing.value = true
  form.value = { ...exam }
  dialogVisible.value = true
}

const submitForm = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true
      try {
        if (isEditing.value) {
          await axios.post(`/api/exams/update/${form.value.id}`, form.value)
          ElMessage.success('Exam updated successfully')
        } else {
          await axios.post('/api/exams/store', form.value)
          ElMessage.success('Exam created successfully')
        }
        dialogVisible.value = false
        fetchExams()
      } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Operation failed')
      } finally {
        submitting.value = false
      }
    }
  })
}

const confirmDelete = (exam) => {
  ElMessageBox.confirm(
    'Are you sure you want to delete this exam?',
    'Warning',
    {
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel',
      type: 'warning'
    }
  ).then(async () => {
    try {
      await axios.get(`/api/exams/delete/${exam.id}`)
      ElMessage.success('Exam deleted successfully')
      fetchExams()
    } catch (error) {
      ElMessage.error('Failed to delete exam')
    }
  })
}

onMounted(() => {
  fetchExams()
})
</script>

<style scoped>
.exam-container {
  padding: 20px;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.create-btn {
  display: flex;
  align-items: center;
  gap: 8px;
}

.exam-form {
  padding: 20px 0;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style> 