export interface ApiEnvelope<T> {
  success: boolean;
  message: string;
  data: T;
  errors?: string[];
}

export interface PatientItem {
  IdUsuario: number;
  Nome: string;
  Apelido?: string;
  WhatsApp?: string;
  Telefone?: string;
  Email?: string;
  Nascimento?: string;
}

export interface PatientDetail extends PatientItem {
  SexoBio?: string;
  CPF?: string;
  Telefone?: string;
  Endereco?: string;
  Numero?: string;
  Bairro?: string;
  Cidade?: string;
  UF?: string;
}

export interface Professional {
  id: number;
  nome: string;
}

export interface AgendaEvent {
  id: number;
  aluno_id: number;
  paciente_nome: string;
  especialidade_nome: string;
  data_consulta: string;
  hora_inicio_prevista: string;
  hora_fim_prevista?: string;
  status: string;
}

export interface ProntuarioItem {
  id: number;
  consulta_id?: number;
  aluno_id: number;
  data_consulta: string;
  created_at: string;
  conteudo_editado?: string;
  conteudo_ia?: string;
  status: string;
}

export interface ProntuarioPatient {
  aluno_id: number;
  paciente_nome: string;
}

export interface AnamneseItem {
  id: number;
  consulta_id: number;
  aluno_id: number;
  acompanhamento_psicologico?: string | null;
  medicacoes_psicotropicos?: string | null;
  internacao_psiquiatrica?: string | null;
  sentimento_ultimos_meses?: string | null;
  atividades_fazem_bem?: string | null;
  dificuldades_memoria?: string | null;
  qualidade_sono?: string | null;
  uso_alcool_substancias?: string | null;
  observacoes_gerais?: string | null;
  crenca_religiao?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface DashboardSummary {
  date: string;
  upcomingCount: number;
  completedCount: number;
  pendingConfirmation: number;
  estimatedRevenue: number;
  appointments: AgendaEvent[];
}
