import ExamList from '../components/Exam/ExamList.vue'

// Add this route in the appropriate section
{
  path: '/exams',
  name: 'exams',
  component: ExamList,
  meta: {
    requiresAuth: true,
    title: 'Exams'
  }
} 