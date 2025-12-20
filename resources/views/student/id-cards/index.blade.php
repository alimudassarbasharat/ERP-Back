<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Cards & PDF Templates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .template-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            background-color: #f8f9fa;
        }
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .template-card {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .template-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .template-preview {
            width: 100%;
            height: 150px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        .template-actions {
            display: flex;
            gap: 0.5rem;
        }
        .section-title {
            color: #0d6efd;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">Student ID Cards & PDF Templates</h1>

        <div class="template-section">
            <h2 class="section-title">Student ID Card Templates (10 Templates)</h2>
            <div class="template-grid">
                @foreach($studentCardTemplates as $key => $name)
                    <div class="template-card">
                        <div class="template-preview">
                            {{ $name }}
                        </div>
                        <h5 class="mb-3">{{ $name }}</h5>
                        <div class="template-actions">
                            <a href="{{ route('student.id-card.preview', ['type' => 'student-card', 'template' => $key]) }}" 
                               class="btn btn-primary btn-sm" 
                               target="_blank">
                                Preview
                            </a>
                            <a href="{{ route('student.id-card.generate', ['template' => $key]) }}" 
                               class="btn btn-success btn-sm">
                                Generate
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="template-section">
            <h2 class="section-title">PDF Templates (20 Templates)</h2>
            <div class="template-grid">
                @foreach($pdfTemplates as $key => $name)
                    <div class="template-card">
                        <div class="template-preview">
                            {{ $name }}
                        </div>
                        <h5 class="mb-3">{{ $name }}</h5>
                        <div class="template-actions">
                            <a href="{{ route('student.id-card.preview', ['type' => 'pdf', 'template' => $key]) }}" 
                               class="btn btn-primary btn-sm" 
                               target="_blank">
                                Preview
                            </a>
                            <a href="{{ route('student.id-card.generate-pdf', ['template' => $key]) }}" 
                               class="btn btn-success btn-sm">
                                Generate
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 