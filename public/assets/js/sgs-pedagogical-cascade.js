/**
 * SGS Pedagogical Cascade - Reusable Frontend Component
 * Standardized hierarchical selection:
 * Cycle -> Niveau -> Série (if Lycée) -> Numéro -> Classe ID -> Matières & Enseignants
 */
class SGSPedagogicalCascade {
    constructor(config) {
        this.cycleEl = document.getElementById(config.cycleId);
        this.niveauEl = document.getElementById(config.niveauId);
        this.groupSerieEl = config.groupSerieId ? document.getElementById(config.groupSerieId) : null;
        this.serieEl = config.serieId ? document.getElementById(config.serieId) : null;
        this.numeroEl = config.numeroId ? document.getElementById(config.numeroId) : null;
        this.classeEl = config.classeId ? document.getElementById(config.classeId) : null;
        this.matiereEl = config.matiereId ? document.getElementById(config.matiereId) : null;
        this.teacherEl = config.teacherId ? document.getElementById(config.teacherId) : null;

        this.includeAllSubjects = config.includeAllSubjects !== undefined ? config.includeAllSubjects : true;
        this.autoSubmitOnClassChange = config.autoSubmitOnClassChange || false;
        this.form = config.formId ? document.getElementById(config.formId) : null;

        this.initialValues = config.initialValues || {};
        this.isRehydrating = false;

        this.init();
    }

    resetSelect(el, defaultText) {
        if (!el) return;
        if (el.tagName === 'INPUT') {
            el.value = '';
        } else {
            el.innerHTML = `<option value="">${defaultText}</option>`;
            el.disabled = false;
        }
    }

    async loadNiveaux(cycleId, selectedNiveau = '') {
        this.resetSelect(this.niveauEl, '-- Choisir d\'abord un cycle --');
        if (this.groupSerieEl) this.groupSerieEl.style.display = 'none';
        if (this.serieEl) this.resetSelect(this.serieEl, '-- Toutes les séries --');
        if (this.numeroEl) this.resetSelect(this.numeroEl, '-- Tous les numéros --');
        if (this.classeEl) this.resetSelect(this.classeEl, '');
        if (this.matiereEl) this.resetSelect(this.matiereEl, '-- Sélectionner la classe --');
        if (this.teacherEl) this.resetSelect(this.teacherEl, '-- Choisir d\'abord un cycle --');

        if (!cycleId) return;

        // Load Teachers eligible for cycle if teacher select exists
        if (this.teacherEl) {
            try {
                const resTeachers = await fetch(`/affectations-pedagogiques/get-enseignants?cycle_id=${cycleId}`);
                const teachers = await resTeachers.json();
                this.teacherEl.disabled = false;
                this.teacherEl.innerHTML = '<option value="">-- Tous les enseignants éligibles --</option>';
                teachers.forEach(t => {
                    const sel = (this.initialValues.teacherId && String(this.initialValues.teacherId) === String(t.id_user)) ? 'selected' : '';
                    this.teacherEl.innerHTML += `<option value="${t.id_user}" ${sel}>${t.prenom} ${t.nom} (${t.identifiant_public || 'ENS'})</option>`;
                });
            } catch (err) {
                console.error('Error fetching teachers:', err);
            }
        }

        // Load Niveaux for cycle
        if (this.niveauEl) {
            try {
                const resNiveaux = await fetch(`/affectations-pedagogiques/get-niveaux?cycle_id=${cycleId}`);
                const niveaux = await resNiveaux.json();
                this.niveauEl.disabled = false;
                this.niveauEl.innerHTML = '<option value="">-- Tous les niveaux --</option>';
                niveaux.forEach(n => {
                    const sel = (selectedNiveau && selectedNiveau === n) ? 'selected' : '';
                    this.niveauEl.innerHTML += `<option value="${n}" ${sel}>${n}</option>`;
                });
            } catch (err) {
                console.error('Error fetching niveaux:', err);
            }
        }
    }

    async loadSeriesOrNumeros(cycleId, niveau, selectedSerie = '', selectedNumero = '') {
        if (this.groupSerieEl) this.groupSerieEl.style.display = 'none';
        if (this.serieEl) this.resetSelect(this.serieEl, '-- Toutes les séries --');
        if (this.numeroEl) this.resetSelect(this.numeroEl, '-- Tous les numéros --');
        if (this.classeEl) this.resetSelect(this.classeEl, '');
        if (this.matiereEl) this.resetSelect(this.matiereEl, '-- Sélectionner la classe --');

        if (!niveau || !cycleId) return;

        try {
            const resSeries = await fetch(`/affectations-pedagogiques/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`);
            const series = await resSeries.json();

            if (series && series.length > 0) {
                if (this.groupSerieEl) this.groupSerieEl.style.display = 'block';
                if (this.serieEl) {
                    this.serieEl.disabled = false;
                    this.serieEl.innerHTML = '<option value="">-- Toutes les séries --</option>';
                    series.forEach(s => {
                        const sel = (selectedSerie && selectedSerie === s) ? 'selected' : '';
                        this.serieEl.innerHTML += `<option value="${s}" ${sel}>${s}</option>`;
                    });
                }
                if (selectedSerie) {
                    await this.loadNumeros(cycleId, niveau, selectedSerie, selectedNumero);
                }
            } else {
                if (this.groupSerieEl) this.groupSerieEl.style.display = 'none';
                await this.loadNumeros(cycleId, niveau, '', selectedNumero);
            }
        } catch (err) {
            console.error('Error fetching series:', err);
        }
    }

    async loadNumeros(cycleId, niveau, serie = '', selectedNumero = '') {
        if (this.numeroEl) this.resetSelect(this.numeroEl, '-- Tous les numéros --');
        if (this.classeEl) this.resetSelect(this.classeEl, '');
        if (this.matiereEl) this.resetSelect(this.matiereEl, '-- Sélectionner la classe --');

        if (!niveau || !cycleId) return;

        try {
            const res = await fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&cycle_id=${cycleId}`);
            const numeros = await res.json();

            if (this.numeroEl) {
                this.numeroEl.disabled = false;
                this.numeroEl.innerHTML = '<option value="">-- Tous les numéros --</option>';
                numeros.forEach(num => {
                    const sel = (selectedNumero && String(selectedNumero) === String(num)) ? 'selected' : '';
                    this.numeroEl.innerHTML += `<option value="${num}" ${sel}>${num}</option>`;
                });
            }
        } catch (err) {
            console.error('Error fetching numeros:', err);
        }
    }

    async resolveClasseAndLoadMatieres(cycleId, niveau, serie = '', numero = '', selectedMatiere = '') {
        if (this.matiereEl) this.resetSelect(this.matiereEl, '-- Sélectionner la classe --');

        if (!cycleId || !niveau || !numero) return;

        try {
            const resClass = await fetch(`/affectations-pedagogiques/get-classe-id?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&numero=${encodeURIComponent(numero)}&cycle_id=${cycleId}`);
            const dataClass = await resClass.json();

            if (dataClass.id_classe) {
                if (this.classeEl) {
                    this.classeEl.value = dataClass.id_classe;
                }

                if (this.matiereEl) {
                    const incFlag = this.includeAllSubjects ? 1 : 0;
                    const resMatieres = await fetch(`/affectations-pedagogiques/get-matieres?classe_id=${dataClass.id_classe}&include_all=${incFlag}`);
                    const matieres = await resMatieres.json();

                    this.matiereEl.disabled = false;
                    this.matiereEl.innerHTML = '<option value="">-- Toutes les matières --</option>';
                    matieres.forEach(m => {
                        const sel = (selectedMatiere && String(selectedMatiere) === String(m.id_matiere)) ? 'selected' : '';
                        this.matiereEl.innerHTML += `<option value="${m.id_matiere}" ${sel}>${m.nom_matiere}</option>`;
                    });
                }

                if (this.autoSubmitOnClassChange && this.form && !this.isRehydrating) {
                    this.form.submit();
                }
            }
        } catch (err) {
            console.error('Error resolving class and subjects:', err);
        }
    }

    init() {
        if (!this.cycleEl) return;

        this.cycleEl.addEventListener('change', () => {
            const cycleId = this.cycleEl.value;
            this.loadNiveaux(cycleId);
        });

        if (this.niveauEl) {
            this.niveauEl.addEventListener('change', () => {
                const cycleId = this.cycleEl.value;
                const niveau = this.niveauEl.value;
                this.loadSeriesOrNumeros(cycleId, niveau);
            });
        }

        if (this.serieEl) {
            this.serieEl.addEventListener('change', () => {
                const cycleId = this.cycleEl.value;
                const niveau = this.niveauEl ? this.niveauEl.value : '';
                const serie = this.serieEl.value;
                this.loadNumeros(cycleId, niveau, serie);
            });
        }

        if (this.numeroEl) {
            this.numeroEl.addEventListener('change', () => {
                const cycleId = this.cycleEl.value;
                const niveau = this.niveauEl ? this.niveauEl.value : '';
                const isSerieVisible = this.groupSerieEl && this.groupSerieEl.style.display !== 'none';
                const serie = (isSerieVisible && this.serieEl) ? this.serieEl.value : '';
                const numero = this.numeroEl.value;
                this.resolveClasseAndLoadMatieres(cycleId, niveau, serie, numero);
            });
        }

        // Rehydrate initial values if provided
        (async () => {
            if (this.initialValues.cycleId) {
                this.isRehydrating = true;
                try {
                    await this.loadNiveaux(this.initialValues.cycleId, this.initialValues.niveau);
                    if (this.initialValues.niveau) {
                        await this.loadSeriesOrNumeros(this.initialValues.cycleId, this.initialValues.niveau, this.initialValues.serie, this.initialValues.numero);
                        if (this.initialValues.numero) {
                            await this.resolveClasseAndLoadMatieres(
                                this.initialValues.cycleId,
                                this.initialValues.niveau,
                                this.initialValues.serie,
                                this.initialValues.numero,
                                this.initialValues.matiereId
                            );
                        }
                    }
                } finally {
                    this.isRehydrating = false;
                }
            }
        })();
    }
}
